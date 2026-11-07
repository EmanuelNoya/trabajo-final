@extends('layouts.app')

@section('titulo', $inscripcion->estudiante->apellido . ', ' . $inscripcion->estudiante->nombre)

@php
    use App\Models\UmbralRiesgo;
    use App\Servicios\ModeloRiesgo;

    $porcentaje = fn ($v) => number_format($v * 100, 1, ',', '') . ' %';
    $dos = fn ($v) => number_format($v, 2, ',', '');
    $nivel = $umbral->nivel((float) $estimacion->probabilidad);

    // Aportes menores a 0,05 se consideran sin efecto apreciable
    $principales = collect($contribuciones)->filter(fn ($c) => $c > 0.05)->keys()->take(2);
    $maximo = max(array_map('abs', $contribuciones)) ?: 1;
@endphp

@section('contenido')
<p><a href="{{ route('tablero.show', $curso) }}">← {{ $curso->asignatura }} · Comisión {{ $curso->comision }}</a></p>

<div class="encabezado">
    <h1>{{ $inscripcion->estudiante->apellido }}, {{ $inscripcion->estudiante->nombre }}</h1>
    <span class="detalle">Legajo {{ $inscripcion->estudiante->legajo }}</span>
</div>

{{-- Criterio 1 --}}
<div class="niveles">
    <div class="nivel nivel-{{ $nivel }}">
        <span class="cantidad">{{ $porcentaje($estimacion->probabilidad) }}</span>
        <span>Probabilidad estimada</span>
    </div>
    <div class="nivel nivel-{{ $nivel }}">
        <span class="cantidad">{{ ucfirst($nivel) }}</span>
        <span>{{ UmbralRiesgo::NIVELES[$nivel] }}</span>
    </div>
    <div class="nivel">
        <span class="cantidad">{{ $ejecucion->fecha_corte->format('d/m') }}</span>
        <span>Registros hasta el corte ({{ $ejecucion->fecha_corte->format('d/m/Y') }})</span>
    </div>
</div>

{{-- Criterio 2: lo que más contribuye --}}
<div class="tarjeta seccion">
    <h2>Qué explica su nivel de riesgo</h2>

    @if ($principales->isNotEmpty())
        <p>
            Lo que más eleva su riesgo:
            <strong>{{ $principales->map(fn ($i) => mb_strtolower(ModeloRiesgo::ETIQUETAS[$i]))->join(' y ') }}</strong>.
        </p>
    @else
        <p>Ningún indicador eleva su riesgo de manera apreciable.</p>
    @endif

    <table class="tabla aportes">
        <tbody>
            @foreach ($contribuciones as $indicador => $aporte)
                <tr>
                    <td>{{ ModeloRiesgo::ETIQUETAS[$indicador] }}</td>
                    <td class="barra-celda">
                        <span class="barra {{ $aporte > 0 ? 'eleva' : 'reduce' }}"
                              style="width: {{ round(abs($aporte) / $maximo * 100) }}%"></span>
                    </td>
                    <td class="detalle">
                        @if (abs($aporte) <= 0.05)
                            Sin efecto apreciable
                        @elseif ($aporte > 0)
                            Eleva el riesgo
                        @else
                            Reduce el riesgo
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <p class="ayuda">
        El largo de cada barra muestra cuánto pesa el indicador en la estimación, según el modelo
        {{ $ejecucion->modelo->version }}.
    </p>
</div>

{{-- Criterio 2: comparación con el curso --}}
<div class="tarjeta seccion">
    <h2>Comparación con el promedio del curso</h2>

    <div class="grafico"><canvas id="grafico-comparacion"></canvas></div>

    <table class="tabla seccion">
        <thead>
            <tr><th>Indicador</th><th>Estudiante</th><th>Promedio del curso</th><th>Diferencia</th></tr>
        </thead>
        <tbody>
            @foreach (ModeloRiesgo::INDICADORES as $i)
                @php $diferencia = $valores[$i] - (float) $promedios[$i]; @endphp
                <tr>
                    <td>{{ ModeloRiesgo::ETIQUETAS[$i] }}</td>
                    <td><strong>{{ $dos($valores[$i]) }}</strong></td>
                    <td>{{ $dos($promedios[$i]) }}</td>
                    <td class="{{ $diferencia < 0 ? 'texto-alto' : '' }}">
                        {{ $diferencia > 0 ? '+' : '' }}{{ $dos($diferencia) }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

{{-- Registros que originaron los indicadores --}}
<div class="tarjeta sin-relleno seccion">
    <h2 class="titulo-tabla">Evaluaciones</h2>
    <table class="tabla">
        <thead>
            <tr><th>Evaluación</th><th>Vencimiento</th><th>Entrega</th><th>Estado</th><th>Calificación</th></tr>
        </thead>
        <tbody>
            @forelse ($evaluaciones as $evaluacion)
                @php $entrega = $evaluacion->entregas->first(); @endphp
                <tr>
                    <td>
                        {{ $evaluacion->denominacion }}
                        @if ($evaluacion->fecha_vencimiento->gt($ejecucion->fecha_corte))
                            <div class="detalle">Vence después del corte: no cuenta para entregas</div>
                        @endif
                    </td>
                    <td>{{ $evaluacion->fecha_vencimiento->format('d/m/Y') }}</td>
                    <td>{{ $entrega?->fecha_entrega?->format('d/m/Y') ?? '—' }}</td>
                    <td>
                        @switch($entrega?->estado() ?? 'no_presentada')
                            @case('en_plazo') <span class="etiqueta bajo">En plazo</span> @break
                            @case('fuera_de_plazo') <span class="etiqueta medio">Fuera de plazo</span> @break
                            @default <span class="etiqueta alto">No presentada</span>
                        @endswitch
                    </td>
                    <td>{{ $entrega?->calificacion !== null ? $dos($entrega->calificacion) : '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="5">Sin evaluaciones registradas.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="tarjeta sin-relleno seccion">
    <h2 class="titulo-tabla">Actividad y participación por semana (hasta el corte)</h2>
    <table class="tabla">
        <thead>
            <tr><th>Semana</th><th>Actividad en el aula virtual</th><th>Intervenciones en foros</th></tr>
        </thead>
        <tbody>
            @for ($s = 1; $s <= $semanas; $s++)
                <tr>
                    <td>{{ $s }}</td>
                    <td>{{ ($actividad[$s] ?? false) ? 'Sí' : 'No' }}</td>
                    <td>{{ $foros[$s] ?? 0 }}</td>
                </tr>
            @endfor
        </tbody>
    </table>
</div>

{{-- Evolución --}}
@if ($historial->count() > 1)
    <div class="tarjeta sin-relleno seccion">
        <h2 class="titulo-tabla">Estimaciones del estudiante</h2>
        <table class="tabla">
            <thead>
                <tr><th>Fecha</th><th>Corte</th><th>Probabilidad</th><th>Nivel con los umbrales actuales</th></tr>
            </thead>
            <tbody>
                @foreach ($historial as $anterior)
                    @php $nivelAnterior = $umbral->nivel((float) $anterior->probabilidad); @endphp
                    <tr>
                        <td>
                            {{ $anterior->ejecucion->created_at->format('d/m/Y H:i') }}
                            @if ($loop->first) <span class="etiqueta activa">Actual</span> @endif
                        </td>
                        <td>{{ $anterior->ejecucion->fecha_corte->format('d/m/Y') }}</td>
                        <td>{{ $porcentaje($anterior->probabilidad) }}</td>
                        <td><span class="etiqueta {{ $nivelAnterior }}">{{ UmbralRiesgo::NIVELES[$nivelAnterior] }}</span></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif

<p class="ayuda seccion">
    La probabilidad es una estimación para priorizar el acompañamiento, no un diagnóstico.
</p>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script>
        new Chart(document.getElementById('grafico-comparacion'), {
            type: 'bar',
            data: {
                labels: @json(array_values(App\Servicios\ModeloRiesgo::ETIQUETAS)),
                datasets: [
                    { label: 'Estudiante', data: @json(array_values($valores)), backgroundColor: '#1f4e79' },
                    { label: 'Promedio del curso', data: @json(array_map('floatval', array_values($promedios))), backgroundColor: '#98a2b3' },
                ],
            },
            options: {
                maintainAspectRatio: false,
                scales: { y: { min: 0, max: 1 } },
                plugins: { legend: { position: 'bottom' } },
            },
        });
    </script>
@endpush