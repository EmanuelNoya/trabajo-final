<?php

namespace App\Http\Controllers\Analista;

use App\Http\Controllers\Controller;
use App\Models\Carrera;
use App\Models\Curso;
use App\Models\Usuario;
use App\Models\ActividadSemanal;
use App\Models\Evaluacion;
use App\Models\ParticipacionForo;
use App\Servicios\ArchivoInvalido;
use App\Servicios\ImportadorNomina;
use App\Models\Inscripcion;
use App\Servicios\CalculadoraIndicadores;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CursoController extends Controller
{
    public function index(): View
    {
        $cursos = Curso::with(['carrera', 'docente', 'nominaVigente'])
            ->withCount('inscripciones')
            ->orderByDesc('fecha_inicio')
            ->orderBy('asignatura')
            ->orderBy('comision')
            ->get();

        return view('analista.cursos.index', compact('cursos'));
    }

    public function create(): View
    {
        return view('analista.cursos.create', [
            'carreras' => Carrera::orderBy('nombre')->get(),
            'docentes' => Usuario::where('rol', 'docente')->where('activo', true)
                ->orderBy('apellido')->orderBy('nombre')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'carrera_id' => ['required', Rule::exists('carrera', 'id')],
            'docente_id' => ['required', Rule::exists('usuario', 'id')->where('rol', 'docente')->where('activo', true)],
            'asignatura' => ['required', 'string', 'max:120',
                Rule::unique('curso')->where(fn ($q) => $q
                    ->where('comision', $request->input('comision'))
                    ->where('periodo_lectivo', $request->input('periodo_lectivo'))),
            ],
            'comision' => ['required', 'string', 'max:10'],
            'periodo_lectivo' => ['required', 'string', 'max:20'],
            'modalidad' => ['required', Rule::in(Curso::MODALIDADES)],
            'fecha_inicio' => ['required', 'date'],
            'fecha_fin' => ['required', 'date', 'after:fecha_inicio'],
        ], [
            'asignatura.unique' => 'Ya existe un curso con esa asignatura, comisión y período lectivo.',
        ]);

        $curso = Curso::create($datos);

        return redirect()->route('cursos.show', $curso)
            ->with('estado', 'Curso registrado. Ahora podés incorporar su nómina.');
    }

    public function show(Curso $curso, CalculadoraIndicadores $calculadora): View
    {
        $curso->load(['carrera', 'docente', 'cargas' => fn ($q) => $q->with('usuario')->latest()]);

        $inscripciones = $curso->inscripciones()
            ->with('estudiante')
            ->get()
            ->sortBy(fn ($i) => $i->estudiante->apellido . ' ' . $i->estudiante->nombre);

        $semanasActividad = ActividadSemanal::vigentesDelCurso($curso)->distinct()->orderBy('semana')->pluck('semana');
        $semanasForos = ParticipacionForo::vigentesDelCurso($curso)->distinct()->orderBy('semana')->pluck('semana');

        $evaluaciones = Evaluacion::vigentesDelCurso($curso)
            ->withCount([
                'entregas as en_plazo' => fn ($q) => $q->whereColumn('entrega.fecha_entrega', '<=', 'evaluacion.fecha_vencimiento'),
                'entregas as fuera_de_plazo' => fn ($q) => $q->whereColumn('entrega.fecha_entrega', '>', 'evaluacion.fecha_vencimiento'),
                'entregas as no_presentadas' => fn ($q) => $q->whereNull('entrega.fecha_entrega'),
            ])
            ->withAvg('entregas as promedio', 'calificacion')
            ->orderBy('fecha_vencimiento')
            ->get();

        // Totales por inscripción, para la tabla de estudiantes
        $semanasActivas = ActividadSemanal::vigentesDelCurso($curso)
            ->where('con_actividad', true)
            ->selectRaw('inscripcion_id, COUNT(*) AS total')
            ->groupBy('inscripcion_id')
            ->pluck('total', 'inscripcion_id');

        $intervenciones = ParticipacionForo::vigentesDelCurso($curso)
            ->selectRaw('inscripcion_id, SUM(intervenciones) AS total')
            ->groupBy('inscripcion_id')
            ->pluck('total', 'inscripcion_id');

        // "semanas 1 a 6", o la lista si hay saltos
        $rango = function ($semanas) {
            if ($semanas->isEmpty()) {
                return null;
            }

            return $semanas->max() - $semanas->min() + 1 === $semanas->count()
                ? "semanas {$semanas->min()} a {$semanas->max()}"
                : 'semanas ' . $semanas->implode(', ');
        };

        // Estado de cada archivo requerido (Figura 7)
        $definiciones = [
            'nomina' => [
                'nombre' => 'Nómina',
                'contenido' => 'legajo, apellido, nombre, documento, fecha_baja',
                'ruta' => route('cursos.nomina', $curso),
                'reemplazo' => 'Reemplazar',
                'detalle' => $inscripciones->count() . ' estudiantes',
            ],
            'actividad' => [
                'nombre' => 'Actividad en el aula virtual',
                'contenido' => 'legajo, semana, con_actividad',
                'ruta' => route('cursos.incorporar', [$curso, 'actividad']),
                'reemplazo' => 'Agregar semanas',
                'detalle' => $rango($semanasActividad),
            ],
            'calificaciones' => [
                'nombre' => 'Calificaciones y entregas',
                'contenido' => 'legajo, evaluacion, tipo, fecha_vencimiento, fecha_entrega, calificacion',
                'ruta' => route('cursos.incorporar', [$curso, 'calificaciones']),
                'reemplazo' => 'Reemplazar',
                'detalle' => $evaluaciones->count() . ' evaluaciones',
            ],
            'foros' => [
                'nombre' => 'Participación en foros',
                'contenido' => 'legajo, semana, intervenciones',
                'ruta' => route('cursos.incorporar', [$curso, 'foros']),
                'reemplazo' => 'Agregar semanas',
                'detalle' => $rango($semanasForos),
            ],
        ];

        $vigentes = $curso->cargas->where('estado', 'vigente'); // de la más reciente a la más antigua

        $archivos = collect($definiciones)->map(function (array $definicion, string $tipo) use ($vigentes) {
            $ultima = $vigentes->firstWhere('tipo_archivo', $tipo);

            return $definicion + [
                'ultima' => $ultima,
                'estado' => match (true) {
                    $ultima === null => 'pendiente',
                    $ultima->filas_rechazadas > 0 => 'observaciones',
                    default => 'incorporado',
                },
                'accion' => $ultima ? $definicion['reemplazo'] : 'Seleccionar archivo',
            ];
        });

        // Lo que falta para poder estimar (HU-07, criterios 1 y 3)
        $pendientes = $calculadora->verificar($curso);
        $semanaCorte = $calculadora->semanasHastaCorte($curso);

        return view('analista.cursos.show', compact(
            'curso', 'inscripciones', 'semanasActividad', 'evaluaciones', 'semanasForos',
            'semanasActivas', 'intervenciones', 'archivos', 'pendientes', 'semanaCorte'
        ));
    }

    public function incorporarNomina(Request $request, Curso $curso, ImportadorNomina $importador): RedirectResponse
    {
        $request->validate([
            'archivo' => ['required', 'file', 'extensions:csv', 'max:2048'],
        ]);

        try {
            $resultado = $importador->importar($request->file('archivo'), $curso, $request->user());
        } catch (ArchivoInvalido $e) {
            return back()->withErrors(['archivo' => "No se incorporó el archivo. {$e->getMessage()}"]);
        }

        if ($resultado['carga'] === null) {
            return back()
                ->withErrors(['archivo' => 'Ninguna fila superó la validación: no se incorporó el archivo.'])
                ->with('rechazadas', $resultado['rechazadas'])
                ->with('resultado', [
                    'archivo' => $request->file('archivo')->getClientOriginalName(),
                    'validas' => 0,
                    'rechazadas' => count($resultado['rechazadas']),
                    'extra' => null,
                ]);
        }

        $carga = $resultado['carga'];

        return back()
            ->with('rechazadas', $resultado['rechazadas'])
            ->with('resultado', [
                'archivo' => $carga->nombre_archivo,
                'validas' => $carga->filas_validas,
                'rechazadas' => $carga->filas_rechazadas,
                'extra' => [
                    Inscripcion::where('carga_id', $carga->id)->whereNotNull('fecha_baja')->count(),
                    'estudiantes con fecha de baja',
                ],
            ]);
    }
}