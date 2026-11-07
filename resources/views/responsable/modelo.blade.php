@extends('layouts.app')

@section('titulo', 'Importancia de indicadores')

@php
    use App\Servicios\ModeloRiesgo;

    $decimal = fn ($v) => number_format((float) $v, 2, ',', '');
    $pct = fn ($v) => number_format($v * 100, 1, ',', '') . ' %';
@endphp

@section('contenido')
<div class="encabezado">
    <div>
        <h1>Importancia de los indicadores</h1>
        @if ($modelo)
            {{-- HU-12, criterio 2 --}}
            <p class="subtitulo">
                Modelo {{ $modelo->version }} · {{ $modelo->algoritmo }} ·
                entrenado el {{ $modelo->fecha_entrenamiento->format('d/m/Y') }}
            </p>
        @endif
    </div>
</div>

@if (! $modelo)
    <div class="tarjeta"><p>No hay ningún modelo vigente registrado.</p></div>
@else
    <div class="resumen">
        <div class="dato">
            <span class="numero">{{ $decimal($modelo->sensibilidad) }}</span>
            <span class="rotulo">Sensibilidad: proporción de estudiantes en riesgo que el modelo detecta</span>
        </div>
        <div class="dato">
            <span class="numero">{{ $decimal($modelo->medida_f1) }}</span>
            <span class="rotulo">Medida F1: equilibrio entre detectar y no dar falsas alarmas</span>
        </div>
        <div class="dato">
            <span class="numero">{{ number_format($modelo->corte * 100, 0) }} %</span>
            <span class="rotulo">Momento de corte: estima con los registros hasta esa parte del curso</span>
        </div>
    </div>

    {{-- HU-12, criterio 1 --}}
    <div class="tarjeta sin-relleno seccion">
        <h2 class="titulo-tabla">Indicadores, de mayor a menor importancia</h2>

        @if ($importancias->isEmpty())
            <p class="nota">La importancia de este modelo no está registrada. Registrala con <code>php artisan modelo:importancia</code>.</p>
        @else
            <table class="tabla tabla-indicadores">
                <thead>
                    <tr><th>Indicador</th><th><span class="detalle">Importancia</span></th><th>Importancia relativa</th><th>Efecto</th></tr>
                </thead>
                <tbody>
                    @foreach ($importancias as $importancia)
                        @php $relativa = (float) $importancia->importancia / $total; @endphp
                        <tr>
                            <td class="contribuye">{{ ModeloRiesgo::ETIQUETAS[$importancia->indicador] }}</td>
                            <td class="barra-indicador">
                                <span class="pista" aria-hidden="true">
                                    <span class="relleno" style="width: {{ round($relativa * 100) }}%"></span>
                                </span>
                            </td>
                            <td><strong>{{ $pct($relativa) }}</strong></td>
                            <td>
                                @if (isset($coeficientes[$importancia->indicador]))
                                    {{ $coeficientes[$importancia->indicador] < 0 ? 'A mayor valor, menor riesgo' : 'A mayor valor, mayor riesgo' }}
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <p class="nota">
                La importancia mide cuánto empeora el modelo cuando se mezclan al azar los valores de un indicador
                (importancia por permutación). El porcentaje es su parte en la suma de los cuatro.
                Métricas obtenidas en la validación temporal del entrenamiento.
            </p>
        @endif
    </div>
@endif
@endsection