<?php

namespace App\Http\Controllers\Analista;

use App\Http\Controllers\Controller;
use App\Models\Curso;
use App\Servicios\ErrorEstimacion;
use App\Servicios\EstimadorRiesgo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EstimacionController extends Controller
{
    public function store(Request $request, Curso $curso, EstimadorRiesgo $estimador): RedirectResponse
    {
        try {
            $ejecucion = $estimador->ejecutar($curso, $request->user());
        } catch (ErrorEstimacion $e) {
            return back()->withErrors(['estimacion' => $e->getMessage()]);
        }

        $cantidad = $ejecucion->estimaciones()->count();

        return redirect()->route('cursos.indicadores', $curso)
            ->with('estado', "Estimación registrada para {$cantidad} estudiantes con el modelo {$ejecucion->modelo->version}. "
                . 'Los resultados quedan disponibles en el tablero del docente.');
    }
}