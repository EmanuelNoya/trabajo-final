@extends('layouts.app')

@section('titulo', 'Cursos de la carrera')

@php
    $pct = fn ($v) => number_format($v, 1, ',', '') . "\u{00A0}%";
    $carreras = $filas->pluck('curso.carrera.nombre')->unique()->implode(' · ');
@endphp

@section('contenido')
<div class="encabezado">
    <div>
        <h1>Cursos de la carrera</h1>
        <p class="subtitulo">{{ $carreras ?: 'Sin cursos registrados' }}</p>
    </div>
</div>

<div class="tarjeta sin-relleno">
    <table class="tabla">
        <thead>
            <tr>
                <th>Asignatura</th>
                <th>Comisión</th>
                <th>Docente</th>
                <th>Estudiantes</th>
                <th class="cifra">Alto</th>
                <th class="cifra">Medio</th>
                <th class="cifra">Bajo</th>
                <th>Última estimación</th>
                <th><span class="detalle">Tablero</span></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($filas as $fila)
                @php $curso = $fila['curso']; @endphp
                <tr>
                    <td>
                        {{ $curso->asignatura }}
                        <div class="detalle">{{ $curso->periodo_lectivo }}</div>
                    </td>
                    <td>{{ $curso->comision }}</td>
                    <td>{{ $curso->docente->apellido }}, {{ $curso->docente->nombre }}</td>

                    @if ($fila['conteo'] === null)
                        @php
                            $faltanArchivos = collect($fila['pendientes'])
                                ->contains(fn ($p) => str_starts_with($p, 'Falta') || str_contains($p, 'llega hasta'));
                        @endphp
                        <td>{{ $curso->inscripciones_count ?: '—' }}</td>
                        <td>—</td>
                        <td>—</td>
                        <td>—</td>
                        <td>
                            <span class="etiqueta observaciones" title="{{ implode(' ', $fila['pendientes']) }}">
                                @if ($faltanArchivos)
                                    Faltan archivos
                                @elseif ($fila['pendientes'] !== [])
                                    Antes del corte
                                @else
                                    Sin estimar
                                @endif
                            </span>
                        </td>
                        <td></td>
                    @else
                        <td>{{ $fila['total'] }}</td>
                        <td class="texto-alto">{{ $fila['conteo']['alto'] }} ({{ $pct($fila['porcentaje']['alto']) }})</td>
                        <td>{{ $fila['conteo']['medio'] }} <span class="detalle">({{ $pct($fila['porcentaje']['medio']) }})</span></td>
                        <td>{{ $fila['conteo']['bajo'] }} <span class="detalle">({{ $pct($fila['porcentaje']['bajo']) }})</span></td>
                        <td>{{ $fila['ejecucion']->created_at->format('d/m/Y') }}</td>
                        <td class="acciones"><a href="{{ route('tablero.show', $curso) }}" class="ver">Ver tablero</a></td>
                    @endif
                </tr>
            @empty
                <tr><td colspan="9">No hay cursos en tu carrera.</td></tr>
            @endforelse
        </tbody>
    </table>

    <p class="nota">
        Ordenados de mayor a menor proporción de estudiantes en riesgo alto, según la última estimación de cada curso
        y los umbrales vigentes de la carrera. Pasá el cursor sobre la etiqueta de un curso sin estimación para ver qué falta.
    </p>
</div>
@endsection