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

        return view('tablero.estudiante', compact(
            'curso', 'inscripcion', 'ejecucion', 'estimacion', 'umbral', 'valores',
            'contribuciones', 'promedios', 'semanas', 'actividad', 'foros', 'evaluaciones', 'historial'
        ));
    }
}