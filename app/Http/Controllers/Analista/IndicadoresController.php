<?php

namespace App\Http\Controllers\Analista;

use App\Http\Controllers\Controller;
use App\Models\Curso;
use App\Models\Modelo;
use App\Servicios\CalculadoraIndicadores;
use Illuminate\View\View;

class IndicadoresController extends Controller
{
    public function __invoke(Curso $curso, CalculadoraIndicadores $calculadora): View
    {
        $problemas = $calculadora->verificar($curso);

        return view('analista.indicadores.show', [
            'curso' => $curso,
            'corte' => $calculadora->fechaCorte($curso),
            'semanas' => $calculadora->semanasHastaCorte($curso),
            'problemas' => $problemas,
            'resultado' => $problemas === [] ? $calculadora->calcular($curso) : null,
            'modelo' => Modelo::where('vigente', true)->first(),
            'ejecuciones' => $curso->ejecuciones()
                ->with(['modelo', 'usuario'])
                ->withCount(['estimaciones', 'cargas'])
                ->latest()
                ->get(),
        ]);
    }
}