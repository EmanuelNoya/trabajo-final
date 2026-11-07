<?php

namespace App\Http\Controllers\Responsable;

use App\Http\Controllers\Controller;
use App\Models\Curso;
use App\Models\UmbralRiesgo;
use App\Servicios\CalculadoraIndicadores;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EstadoCarreraController extends Controller
{
    public function __invoke(Request $request, CalculadoraIndicadores $calculadora): View
    {
        $cursos = Curso::visiblesPara($request->user())
            ->with(['carrera', 'docente', 'ejecucionActual.estimaciones:id,ejecucion_id,probabilidad'])
            ->get();

        $umbrales = []; // carrera_id => umbral vigente (una consulta por carrera)

        $filas = $cursos->map(function (Curso $curso) use (&$umbrales, $calculadora) {
            $ejecucion = $curso->ejecucionActual;

            // Criterio 3: sin estimación, se informa qué falta
            if ($ejecucion === null) {
                return ['curso' => $curso, 'conteo' => null, 'pendientes' => $calculadora->verificar($curso)];
            }

            $umbral = $umbrales[$curso->carrera_id] ??= UmbralRiesgo::vigenteDe($curso->carrera_id);
            $conteo = ['alto' => 0, 'medio' => 0, 'bajo' => 0];

            foreach ($ejecucion->estimaciones as $estimacion) {
                $conteo[$umbral->nivel((float) $estimacion->probabilidad)]++;
            }

            $total = max(1, array_sum($conteo));

            return [
                'curso' => $curso,
                'ejecucion' => $ejecucion,
                'total' => array_sum($conteo),
                'conteo' => $conteo, // criterio 1
                'porcentaje' => array_map(fn ($n) => $n / $total * 100, $conteo),
            ];
        })
        ->sort(function ($a, $b) {
            // Sin estimación, al final
            if ($a['conteo'] === null || $b['conteo'] === null) {
                return ($a['conteo'] === null) <=> ($b['conteo'] === null);
            }

            // Mayor proporción en riesgo alto primero; a igualdad, mayor proporción en riesgo medio
            return [$b['porcentaje']['alto'], $b['porcentaje']['medio']]
                <=> [$a['porcentaje']['alto'], $a['porcentaje']['medio']];
        })
        ->values();

        return view('responsable.estado', compact('filas'));
    }
}