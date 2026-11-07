@extends('layouts.app')

@section('titulo', 'Modelo de riesgo')

@php
    use App\Servicios\ModeloRiesgo;
    $porcentaje = fn ($v) => number_format($v * 100, 1, ',', '') . ' %';
@endphp

@section('contenido')
<div class="encabezado">
    <h1>Modelo de riesgo</h1>
</div>

@if (! $modelo)
    <div class="tarjeta"><p>No hay ningún modelo vigente registrado.</p></div>
@else
    {{-- Criterio 2 --}}
    <div class="niveles">
        <div class="nivel">
            <span class="cantidad">{{ $modelo->version }}</span>
            <span>Versión vigente · entrenado el {{ $modelo->fecha_entrenamiento->format('d/m/Y') }}</span>
        </div>
        <div class="nivel">
            <span class="cantidad">{{ number_format($modelo->sensibilidad, 2, ',', '') }}</span>
            <span>Sensibilidad: proporción de estudiantes en riesgo que el modelo detecta</span>
        </div>
        <div class="nivel">
            <span class="cantidad">{{ number_format($modelo->medida_f1, 2, ',', '') }}</span>
            <span>Medida F1: equilibrio entre detectar y no dar falsas alarmas</span>
        </div>
    </div>

    <p class="ayuda seccion">
        {{ $modelo->algoritmo }}. Estima con los registros hasta el {{ $porcentaje($modelo->corte) }} del curso.
        Métricas obtenidas en la validación temporal del entrenamiento.
    </p>

    {{-- Criterio 1 --}}
    <div class="tarjeta seccion">
        <h2>Importancia de los indicadores</h2>

        @if ($importancias->isEmpty())
            <p>La importancia de este modelo no está registrada. Registrala con <code>php artisan modelo:importancia</code>.</p>
        @else
            <table class="tabla aportes">
                <tbody>
                    @foreach ($importancias as $importancia)
                        @php $relativa = (float) $importancia->importancia / $total; @endphp
                        <tr>
                            <td>{{ ModeloRiesgo::ETIQUETAS[$importancia->indicador] }}</td>
                            <td class="barra-celda">
                                <span class="barra" style="width: {{ round($relativa * 100) }}%; background: var(--primario)"></span>
                            </td>
                            <td><strong>{{ $porcentaje($relativa) }}</strong></td>
                            <td class="detalle">
                                @if (isset($coeficientes[$importancia->indicador]))
                                    {{ $coeficientes[$importancia->indicador] < 0 ? 'A mayor valor, menor riesgo' : 'A mayor valor, mayor riesgo' }}
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <p class="ayuda">
                La importancia mide cuánto empeora el modelo cuando se mezclan al azar los valores de un indicador
                (importancia por permutación). El porcentaje es su parte en la suma de las cuatro.
            </p>
        @endif
    </div>
@endif
@endsection