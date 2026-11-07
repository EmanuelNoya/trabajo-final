<?php

namespace App\Http\Controllers;

use App\Models\Curso;
use App\Models\UmbralRiesgo;
use App\Servicios\CalculadoraIndicadores;
use App\Models\ActividadSemanal;
use App\Models\Estimacion;
use App\Models\Evaluacion;
use App\Models\Inscripcion;
use App\Models\ParticipacionForo;
use App\Servicios\ModeloRiesgo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TableroController extends Controller
{
    public function index(Request $request): View
    {
        $cursos = Curso::visiblesPara($request->user())
            ->with(['carrera', 'docente', 'ejecucionActual'])
            ->orderByDesc('fecha_inicio')
            ->orderBy('asignatura')
            ->orderBy('comision')
            ->get();

        return view('tablero.index', compact('cursos'));
    }

    public function show(Request $request, Curso $curso, CalculadoraIndicadores $calculadora): View
    {
        Gate::authorize('verTablero', $curso);

        $curso->load(['carrera', 'docente']);
        $filtro = $this->filtro($request);
        $datos = $this->listado($curso, $filtro);

        // HU-09, criterio 3: sin estimación, se indican los pasos que faltan
        if ($datos === null) {
            return view('tablero.sin-estimacion', [
                'curso' => $curso,
                'pendientes' => $calculadora->verificar($curso),
            ]);
        }

        return view('tablero.show', ['curso' => $curso, 'filtro' => $filtro] + $datos);
    }

    /**
     * HU-14: el mismo listado del tablero, en el mismo orden y con el mismo filtro, como CSV.
     */
    public function exportar(Request $request, Curso $curso): StreamedResponse
    {
        Gate::authorize('verTablero', $curso);

        $filtro = $this->filtro($request);
        $datos = $this->listado($curso, $filtro);
        abort_if($datos === null, 404);

        $nombre = Str::slug("{$curso->asignatura} {$curso->comision} {$curso->periodo_lectivo}")
            . ($filtro ? "-riesgo-{$filtro}" : '')
            . '.csv';

        $decimal = fn ($v) => number_format((float) $v, 4, ',', '');

        return response()->streamDownload(function () use ($datos, $decimal) {
            $salida = fopen('php://output', 'w');
            fwrite($salida, "\xEF\xBB\xBF"); // BOM: Excel reconoce UTF-8 y muestra bien las tildes

            fputcsv($salida, [
                'legajo', 'apellido y nombre', 'probabilidad', 'nivel',
                'asistencia', 'calificaciones', 'entregas', 'participacion',
            ], ';', '"', '');

            foreach ($datos['lista'] as $estimacion) {
                $estudiante = $estimacion->inscripcion->estudiante;

                fputcsv($salida, [
                    $estudiante->legajo,
                    "{$estudiante->apellido}, {$estudiante->nombre}",
                    $decimal($estimacion->probabilidad),
                    UmbralRiesgo::NIVELES[$estimacion->nivel],
                    $decimal($estimacion->asistencia),
                    $decimal($estimacion->calificaciones),
                    $decimal($estimacion->entregas),
                    $decimal($estimacion->participacion),
                ], ';', '"', '');
            }

            fclose($salida);
        }, $nombre, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * Estimación actual del curso con el nivel de cada estudiante, de mayor a menor probabilidad.
     * La usan el tablero y la exportación: así el archivo es exactamente lo que se ve.
     */
    private function listado(Curso $curso, ?string $filtro): ?array
    {
        $ejecucion = $curso->ejecucionActual()->with('modelo')->first();

        if ($ejecucion === null) {
            return null;
        }

        $umbral = UmbralRiesgo::vigenteDe($curso->carrera_id);

        $estudiantes = $ejecucion->estimaciones()
            ->with('inscripcion.estudiante')
            ->orderByDesc('probabilidad')
            ->get()
            ->each(fn ($e) => $e->nivel = $umbral->nivel((float) $e->probabilidad));

        $conteo = collect(UmbralRiesgo::NIVELES)
            ->map(fn ($etiqueta, $nivel) => $estudiantes->where('nivel', $nivel)->count());

        $lista = $filtro ? $estudiantes->where('nivel', $filtro)->values() : $estudiantes;

        return compact('ejecucion', 'umbral', 'conteo', 'lista');
    }

    private function filtro(Request $request): ?string
    {
        $nivel = $request->query('nivel');

        return in_array($nivel, array_keys(UmbralRiesgo::NIVELES), true) ? $nivel : null;
    }

    public function estudiante(Curso $curso, Inscripcion $inscripcion): View
    {
        Gate::authorize('verTablero', $curso); // criterio 3
        abort_unless($inscripcion->curso_id === $curso->id, 404); // la inscripción tiene que ser de este curso

        $curso->load('carrera');
        $inscripcion->load('estudiante');

        $ejecucion = $curso->ejecucionActual()->with('modelo')->firstOrFail();
        $estimacion = $ejecucion->estimaciones()->where('inscripcion_id', $inscripcion->id)->firstOrFail();
        $umbral = UmbralRiesgo::vigenteDe($curso->carrera_id);

        // Criterio 1: indicadores, probabilidad y nivel
        $valores = collect(ModeloRiesgo::INDICADORES)
            ->mapWithKeys(fn ($i) => [$i => (float) $estimacion->{$i}])
            ->all();

        // Criterio 2: aporte de cada indicador según el modelo usado en esa estimación...
        $contribuciones = ModeloRiesgo::cargar($ejecucion->modelo)->contribuciones($valores);

        // ...y comparación con el promedio del curso en la misma estimación
        $promedios = $ejecucion->estimaciones()
            ->selectRaw('AVG(asistencia) AS asistencia, AVG(calificaciones) AS calificaciones,
                        AVG(entregas) AS entregas, AVG(participacion) AS participacion')
            ->first()
            ->only(ModeloRiesgo::INDICADORES);

        // Registros que originaron los indicadores: los de las cargas usadas en esa estimación
        $cargas = $ejecucion->cargas()->pluck('carga_archivo.id');
        $semanas = intdiv((int) $curso->fecha_inicio->diffInDays($ejecucion->fecha_corte), 7) + 1;

        $actividad = ActividadSemanal::where('inscripcion_id', $inscripcion->id)
            ->whereIn('carga_id', $cargas)
            ->pluck('con_actividad', 'semana');

        $foros = ParticipacionForo::where('inscripcion_id', $inscripcion->id)
            ->whereIn('carga_id', $cargas)
            ->pluck('intervenciones', 'semana');

        $evaluaciones = Evaluacion::whereIn('carga_id', $cargas)
            ->with(['entregas' => fn ($q) => $q->where('inscripcion_id', $inscripcion->id)])
            ->orderBy('fecha_vencimiento')
            ->get();

        // Evolución: todas las estimaciones del estudiante, de la más reciente a la más antigua
        $historial = Estimacion::where('inscripcion_id', $inscripcion->id)
            ->with('ejecucion')
            ->orderByDesc('ejecucion_id')
            ->get();

        // Resumen de los registros hasta el corte (Figura 9)
        $corte = $ejecucion->fecha_corte;
        $propias = $evaluaciones->mapWithKeys(fn ($e) => [$e->id => $e->entregas->first()]);
        $vencidas = $evaluaciones->filter(fn ($e) => $e->fecha_vencimiento->lte($corte));

        $resumen = [
            'semanas_activas' => $actividad->filter(fn ($activa, $semana) => $activa && $semana <= $semanas)->count(),
            'vencidas' => $vencidas->count(),
            'en_plazo' => $vencidas->filter(function ($evaluacion) use ($propias) {
                $entrega = $propias[$evaluacion->id];

                return $entrega?->fecha_entrega !== null
                    && $entrega->fecha_entrega->lte($evaluacion->fecha_vencimiento);
            })->count(),
            'promedio' => $propias
                ->filter(fn ($e) => $e?->fecha_entrega !== null && $e->fecha_entrega->lte($corte) && $e->calificacion !== null)
                ->avg(fn ($e) => (float) $e->calificacion),
            'intervenciones' => $foros->filter(fn ($cantidad, $semana) => $semana <= $semanas)->sum(),
        ];

        return view('tablero.estudiante', compact(
            'curso', 'inscripcion', 'ejecucion', 'estimacion', 'umbral', 'valores',
            'contribuciones', 'promedios', 'semanas', 'actividad', 'foros', 'evaluaciones', 'historial', 'resumen'
        ));
    }
}