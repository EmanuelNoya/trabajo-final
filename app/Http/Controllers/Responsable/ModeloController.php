<?php

namespace App\Http\Controllers\Responsable;

use App\Http\Controllers\Controller;
use App\Models\Modelo;
use App\Servicios\ErrorEstimacion;
use App\Servicios\ModeloRiesgo;
use Illuminate\View\View;

class ModeloController extends Controller
{
    public function __invoke(): View
    {
        $modelo = Modelo::where('vigente', true)->with('importancias')->first();

        // Criterio 1: de mayor a menor importancia
        $importancias = $modelo?->importancias->sortByDesc(fn ($i) => (float) $i->importancia)->values() ?? collect();
        $total = $importancias->sum(fn ($i) => (float) $i->importancia) ?: 1;

        // Sentido del efecto de cada indicador, según el signo de su coeficiente
        try {
            $coeficientes = $modelo ? ModeloRiesgo::cargar($modelo)->datos['coeficientes'] : [];
        } catch (ErrorEstimacion) {
            $coeficientes = [];
        }

        return view('responsable.modelo', compact('modelo', 'importancias', 'total', 'coeficientes'));
    }
}