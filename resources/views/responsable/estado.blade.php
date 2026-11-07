@extends('layouts.app')

@section('titulo', 'Cursos de la carrera')

@php
    use App\Models\UmbralRiesgo;
    $pct = fn ($v) => number_format($v, 0, ',', '') . ' %';
@endphp

@section('contenido')
<div class="encabezado">
    <h1>Estado de los cursos de la carrera</h1>
</div>

<p class="ayuda">
    Ordenados por proporción de estudiantes en riesgo alto, según la última estimación de cada curso
    y los umbrales vigentes de la carrera.
</p>

<div class="tarjeta sin-relleno">
    <table class="tabla">
        <thead>
            <tr>
                <th>Curso</th>
                <th>Docente</th>
                <th>Estudiantes</th>
                @foreach (UmbralRiesgo::NIVELES as $nivel => $etiqueta)
                    <th>{{ $etiqueta }}</th>
                @endforeach
                <th>Distribución</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($filas as $fila)
                <tr>
                    <td>
                        {{-- Criterio 2 --}}
                        <a href="{{ route('tablero.show', $fila['curso']) }}">{{ $fila['curso']->asignatura }}</a>
                        <div class="detalle">Comisión {{ $fila['curso']->comision }} · {{ $fila['curso']->periodo_lectivo }}</div>
                    </td>
                    <td>{{ $fila['curso']->docente->apellido }}, {{ $fila['curso']->docente->nombre }}</td>

                    @if ($fila['conteo'] === null)
                        {{-- Criterio 3 --}}
                        <td colspan="5">
                            <span class="etiqueta inactiva">Sin estimación</span>
                            <div class="detalle">
                                @if ($fila['pendientes'] !== [])
                                    Faltan registros: {{ implode(' ', $fila['pendientes']) }}
                                @else
                                    Los registros están completos: falta ejecutar la estimación.
                                @endif
                            </div>
                        </td>
                    @else
                        <td>
                            {{ $fila['total'] }}
                            <div class="detalle">Estimación del {{ $fila['ejecucion']->created_at->format('d/m/Y') }}</div>
                        </td>
                        @foreach (UmbralRiesgo::NIVELES as $nivel => $etiqueta)
                            <td>{{ $fila['conteo'][$nivel] }} <span class="detalle">· {{ $pct($fila['porcentaje'][$nivel]) }}</span></td>
                        @endforeach
                        <td>
                            <div class="distribucion" title="Alto {{ $pct($fila['porcentaje']['alto']) }} · Medio {{ $pct($fila['porcentaje']['medio']) }} · Bajo {{ $pct($fila['porcentaje']['bajo']) }}">
                                @foreach (['alto', 'medio', 'bajo'] as $nivel)
                                    <span class="{{ $nivel }}" style="width: {{ $fila['porcentaje'][$nivel] }}%"></span>
                                @endforeach
                            </div>
                        </td>
                    @endif
                </tr>
            @empty
                <tr><td colspan="7">No hay cursos en tu carrera.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection