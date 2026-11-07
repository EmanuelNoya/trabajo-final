<?php

namespace App\Http\Controllers\Responsable;

use App\Http\Controllers\Controller;
use App\Models\Carrera;
use App\Models\Curso;
use App\Models\Estimacion;
use App\Models\UmbralRiesgo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class UmbralController extends Controller
{
    public function index(Request $request): View
    {
        $carreras = Carrera::where('responsable_id', $request->user()->id)
            ->orderBy('nombre')
            ->get()
            ->each(fn ($c) => $c->vigente = UmbralRiesgo::vigenteDe($c->id));

        return view('responsable.umbrales.index', compact('carreras'));
    }

    public function edit(Carrera $carrera): View
    {
        Gate::authorize('configurarUmbrales', $carrera);

        $vigente = UmbralRiesgo::vigenteDe($carrera->id);

        // Probabilidades de la estimación actual de cada curso de la carrera, para la vista previa
        $ejecuciones = Curso::where('carrera_id', $carrera->id)
            ->with('ejecucionActual')
            ->get()
            ->pluck('ejecucionActual.id')
            ->filter();

        $probabilidades = Estimacion::whereIn('ejecucion_id', $ejecuciones)
            ->pluck('probabilidad')
            ->map(fn ($p) => (float) $p)
            ->values();

        $actual = collect(UmbralRiesgo::NIVELES)
            ->map(fn ($etiqueta, $nivel) => $probabilidades->filter(fn ($p) => $vigente->nivel($p) === $nivel)->count());

        // Criterio 3: historial completo, con quién hizo cada cambio y desde cuándo rige
        $historial = $carrera->umbrales()
            ->with('usuario')
            ->orderByDesc('fecha_desde')
            ->orderByDesc('id')
            ->get();

        return view('responsable.umbrales.edit', compact(
            'carrera', 'vigente', 'probabilidades', 'actual', 'historial'
        ));
    }

    public function store(Request $request, Carrera $carrera): RedirectResponse
    {
        Gate::authorize('configurarUmbrales', $carrera);

        $datos = $request->validate([
            'umbral_medio' => ['required', 'numeric', 'between:1,99'],
            'umbral_alto' => ['required', 'numeric', 'between:1,99', 'gt:umbral_medio'],
            'fecha_desde' => ['required', 'date', 'after_or_equal:today'],
        ], [
            'umbral_medio.required' => 'Ingresá el umbral de riesgo medio.',
            'umbral_alto.required' => 'Ingresá el umbral de riesgo alto.',
            'umbral_medio.numeric' => 'Los umbrales deben ser números.',
            'umbral_alto.numeric' => 'Los umbrales deben ser números.',
            'umbral_medio.between' => 'El umbral de riesgo medio debe estar entre 1 % y 99 %.',
            'umbral_alto.between' => 'El umbral de riesgo alto debe estar entre 1 % y 99 %.',
            'umbral_alto.gt' => 'El umbral de riesgo alto tiene que ser mayor que el de riesgo medio.', // criterio 1
            'fecha_desde.required' => 'Indicá desde qué fecha rigen.',
            'fecha_desde.date' => 'La fecha no es válida.',
            'fecha_desde.after_or_equal' => 'La fecha desde la que rigen no puede ser anterior a hoy.',
        ]);

        // Siempre una fila nueva: los umbrales anteriores se conservan (criterio 3)
        $umbral = UmbralRiesgo::create([
            'carrera_id' => $carrera->id,
            'usuario_id' => $request->user()->id,
            'umbral_medio' => round($datos['umbral_medio'] / 100, 4),
            'umbral_alto' => round($datos['umbral_alto'] / 100, 4),
            'fecha_desde' => $datos['fecha_desde'],
        ]);

        $mensaje = $umbral->fecha_desde->isToday()
            ? 'Umbrales guardados. Los tableros de la carrera ya los aplican, sin repetir la estimación.'
            : 'Umbrales guardados. Rigen a partir del ' . $umbral->fecha_desde->format('d/m/Y') . '.';

        return redirect()->route('umbrales.edit', $carrera)->with('estado', $mensaje);
    }
}