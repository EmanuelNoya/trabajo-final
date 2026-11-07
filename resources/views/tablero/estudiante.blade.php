@extends('layouts.app')

@section('titulo', $inscripcion->estudiante->apellido . ', ' . $inscripcion->estudiante->nombre)

@php
    use App\Models\UmbralRiesgo;

    $decimal = fn ($v) => number_format((float) $v, 2, ',', '');
    $nivel = $umbral->nivel((float) $estimacion->probabilidad);

    $etiquetas = [
        'asistencia' => 'Asistencia (aula virtual)',
        'calificaciones' => 'Calificaciones',
        'entregas' => 'Entregas',
        'participacion' => 'Participación (percentil)',
    ];

    // HU-10, criterio 2: los dos indicadores que más elevan el riesgo (aporte mayor a 0,05)
    $principales = collect($contribuciones)->filter(fn ($c) => $c > 0.05)->keys()->take(2)->all();
@endphp

@section('contenido')
<div class="encabezado">
    <div>
        <div class="migas"><a href="{{ route('tablero.show', $curso) }}">Tablero del curso</a> › Situación individual</div>
        <h1>{{ $inscripcion->estudiante->apellido }}, {{ $inscripcion->estudiante->nombre }}</h1>
        <p class="subtitulo">
            Legajo {{ $inscripcion->estudiante->legajo }} · {{ $curso->asignatura }} · Comisión {{ $curso->comision }}
        </p>
    </div>
</div>

<div class="ficha">
    {{-- HU-10, criterio 1 --}}
    <div class="tarjeta">
        <span class="rotulo">Probabilidad de riesgo</span>
        <span class="probabilidad-grande {{ $nivel }}">{{ $decimal($estimacion->probabilidad) }}</span>
        <span class="etiqueta {{ $nivel }}">{{ UmbralRiesgo::NIVELES[$nivel] }}</span>
        <p class="nota">
            Estimación del {{ $ejecucion->created_at->format('d/m/Y') }} · corte al 30&nbsp;%
            ({{ $ejecucion->fecha_corte->format('d/m/Y') }}) · Modelo {{ $ejecucion->modelo->version }}
        </p>
    </div>

    {{-- HU-10, criterio 2 --}}
    <div class="tarjeta">
        <h2>Indicadores al momento de corte</h2>

        <table class="tabla tabla-indicadores">
            <thead>
                <tr>
                    <th>Indicador</th>
                    <th><span class="detalle">Valor</span></th>
                    <th>Estudiante</th>
                    <th>Promedio del curso</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($contribuciones as $indicador => $aporte)
                    @php $contribuye = in_array($indicador, $principales, true); @endphp
                    <tr>
                        <td class="{{ $contribuye ? 'contribuye' : '' }}">{{ $etiquetas[$indicador] }}</td>
                        <td class="barra-indicador">
                            <span class="pista" aria-hidden="true">
                                <span class="relleno {{ $contribuye ? 'eleva' : '' }}" style="width: {{ round($valores[$indicador] * 100) }}%"></span>
                            </span>
                        </td>
                        <td><strong>{{ $decimal($valores[$indicador]) }}</strong></td>
                        <td>{{ $decimal($promedios[$indicador]) }}</td>
                        <td>
                            @if ($contribuye)
                                <span class="etiqueta alto">Contribuye al riesgo</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <p class="nota">
            @if ($principales !== [])
                Los indicadores están ordenados por su aporte a la probabilidad, según el modelo. Se señalan los que más la elevan.
            @else
                Ningún indicador eleva su riesgo de manera apreciable.
            @endif
        </p>

        <details class="desplegable" id="detalle-grafico">
            <summary>Ver gráfico de comparación con el curso</summary>
            <div class="grafico"><canvas id="grafico-comparacion"></canvas></div>
        </details>
    </div>
</div>

<div class="tarjeta sin-relleno seccion">
    <h2 class="titulo-tabla">Registros que originan los indicadores</h2>
    <table class="tabla">
        <thead>
            <tr>
                <th>Semanas con actividad</th>
                <th>Entregas vencidas</th>
                <th>Presentadas en plazo</th>
                <th>Promedio de calificaciones</th>
                <th>Intervenciones en foros</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{{ $resumen['semanas_activas'] }} de {{ $semanas }}</td>
                <td>{{ $resumen['vencidas'] }}</td>
                <td>{{ $resumen['en_plazo'] }}</td>
                <td>{{ $resumen['promedio'] !== null ? $decimal($resumen['promedio']) : '—' }}</td>
                <td>{{ $resumen['intervenciones'] }}</td>
            </tr>
        </tbody>
    </table>

    <div class="interior">
        <details class="desplegable">
            <summary>Ver el detalle por evaluación y por semana</summary>

            <table class="tabla seccion">
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
                            <td>{{ $entrega?->calificacion !== null ? $decimal($entrega->calificacion) : '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5">Sin evaluaciones registradas.</td></tr>
                    @endforelse
                </tbody>
            </table>

            <table class="tabla seccion">
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
        </details>
    </div>
</div>

<div class="tarjeta sin-relleno seccion">
    <h2 class="titulo-tabla">Historial de estimaciones</h2>
    <table class="tabla">
        <thead>
            <tr><th>Fecha</th><th>Corte</th><th>Probabilidad</th><th>Nivel</th></tr>
        </thead>
        <tbody>
            @foreach ($historial as $anterior)
                @php $nivelAnterior = $umbral->nivel((float) $anterior->probabilidad); @endphp
                <tr>
                    <td>
                        {{ $anterior->ejecucion->created_at->format('d/m/Y H:i') }}
                        @if ($loop->first) <span class="detalle">· actual</span> @endif
                    </td>
                    <td>{{ $anterior->ejecucion->fecha_corte->format('d/m/Y') }}</td>
                    <td>{{ $decimal($anterior->probabilidad) }}</td>
                    <td><span class="etiqueta {{ $nivelAnterior }}">{{ ucfirst($nivelAnterior) }}</span></td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <p class="nota">
        La estimación orienta la priorización. La decisión de contactar al estudiante y el tipo de acompañamiento
        corresponden al docente y a las áreas académicas. Los niveles se muestran con los umbrales vigentes de la carrera.
    </p>
</div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script>
        // El gráfico se crea la primera vez que se abre el desplegable (cerrado, el lienzo no tiene tamaño)
        const desplegable = document.getElementById('detalle-grafico');
        let grafico = null;

        desplegable.addEventListener('toggle', () => {
            if (!desplegable.open || grafico) return;

            grafico = new Chart(document.getElementById('grafico-comparacion'), {
                type: 'bar',
                data: {
                    labels: @json(array_values($etiquetas)),
                    datasets: [
                        { label: 'Estudiante', data: @json(array_values($valores)), backgroundColor: '#1e3a5f' },
                        { label: 'Promedio del curso', data: @json(array_map('floatval', array_values($promedios))), backgroundColor: '#98a2b3' },
                    ],
                },
                options: {
                    maintainAspectRatio: false,
                    scales: { y: { min: 0, max: 1 } },
                    plugins: { legend: { position: 'bottom' } },
                },
            });
        });
    </script>
@endpush