<?php

namespace App\Http\Controllers;

use App\Models\Curso;
use App\Models\UmbralRiesgo;
use App\Servicios\CalculadoraIndicadores;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

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
        Gate::authorize('verTablero', $curso); // 403 si el curso no es del usuario

        $curso->load(['carrera', 'docente']);
        $ejecucion = $curso->ejecucionActual()->with('modelo')->first();

        // Criterio 3: sin estimación, se indican los pasos que faltan
        if ($ejecucion === null) {
            return view('tablero.sin-estimacion', [
                'curso' => $curso,
                'pendientes' => $calculadora->verificar($curso),
            ]);
        }

        $umbral = UmbralRiesgo::vigenteDe($curso->carrera_id);

        // Criterio 1: de mayor a menor probabilidad, con el nivel derivado de los umbrales vigentes
        $estudiantes = $ejecucion->estimaciones()
            ->with('inscripcion.estudiante')
            ->orderByDesc('probabilidad')
            ->get()
            ->each(fn ($e) => $e->nivel = $umbral->nivel((float) $e->probabilidad));

        $conteo = collect(UmbralRiesgo::NIVELES)
            ->map(fn ($etiqueta, $nivel) => $estudiantes->where('nivel', $nivel)->count());

        // Criterio 2: filtro por nivel
        $filtro = in_array($request->query('nivel'), array_keys(UmbralRiesgo::NIVELES), true)
            ? $request->query('nivel')
            : null;

        $lista = $filtro ? $estudiantes->where('nivel', $filtro)->values() : $estudiantes;

        return view('tablero.show', compact('curso', 'ejecucion', 'umbral', 'conteo', 'lista', 'filtro'));
    }
}