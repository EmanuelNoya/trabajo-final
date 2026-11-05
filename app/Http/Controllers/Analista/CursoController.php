<?php

namespace App\Http\Controllers\Analista;

use App\Http\Controllers\Controller;
use App\Models\Carrera;
use App\Models\Curso;
use App\Models\Usuario;
use App\Servicios\ArchivoInvalido;
use App\Servicios\ImportadorNomina;
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

    public function show(Curso $curso): View
    {
        $curso->load(['carrera', 'docente', 'cargas' => fn ($q) => $q->with('usuario')->latest()]);

        $inscripciones = $curso->inscripciones()
            ->with('estudiante')
            ->get()
            ->sortBy(fn ($i) => $i->estudiante->apellido . ' ' . $i->estudiante->nombre);

        return view('analista.cursos.show', compact('curso', 'inscripciones'));
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
                ->with('rechazadas', $resultado['rechazadas']);
        }

        $carga = $resultado['carga'];

        return back()
            ->with('estado', "Nómina incorporada: {$carga->filas_validas} filas válidas y {$carga->filas_rechazadas} rechazadas.")
            ->with('rechazadas', $resultado['rechazadas']);
    }
}