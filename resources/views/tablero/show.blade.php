@extends('layouts.app')

@section('titulo', 'Tablero · ' . $curso->asignatura)

@php
    use App\Models\UmbralRiesgo;

    $decimal = fn ($v) => number_format((float) $v, 2, ',', '');
    $total = $conteo->sum();
    $semanaCorte = intdiv((int) $curso->fecha_inicio->diffInDays($ejecucion->fecha_corte), 7) + 1;

    // Rango de probabilidad de cada nivel, con los umbrales vigentes de la carrera
    $rangos = [
        'alto' => 'probabilidad ≥ ' . $decimal($umbral->umbral_alto),
        'medio' => $decimal($umbral->umbral_medio) . ' a menos de ' . $decimal($umbral->umbral_alto),
        'bajo' => '< ' . $decimal($umbral->umbral_medio),
    ];
@endphp

@section('contenido')
<div class="encabezado">
    <div>
        <h1>Tablero del curso</h1>
        <p class="subtitulo">
            {{ $curso->asignatura }} · Comisión {{ $curso->comision }} ·
            Estimación del {{ $ejecucion->created_at->format('d/m/Y') }}, al 30&nbsp;% del curso
            (semana {{ $semanaCorte }} de {{ $curso->cantidadSemanas() }}) ·
            Modelo {{ $ejecucion->modelo->version }}
        </p>
    </div>
</div>

{{-- HU-09, criterio 1: cantidad de estudiantes por nivel --}}
<div class="resumen">
    @foreach (UmbralRiesgo::NIVELES as $nivel => $etiqueta)
        <div class="dato">
            <span class="numero {{ $nivel }}">{{ $conteo[$nivel] }}</span>
            <span class="rotulo">{{ $etiqueta }} ({{ $rangos[$nivel] }})</span>
        </div>
    @endforeach
    <div class="dato">
        <span class="numero">{{ $total }}</span>
        <span class="rotulo">Estudiantes estimados</span>
    </div>
</div>

<div class="tarjeta sin-relleno seccion">
    <div class="filtros">
        {{-- HU-09, criterio 2 --}}
        <div class="chips">
            <span>Filtrar:</span>
            <a href="{{ route('tablero.show', $curso) }}" class="chip todos {{ $filtro === null ? 'activo' : '' }}">Todos</a>
            @foreach (UmbralRiesgo::NIVELES as $nivel => $etiqueta)
                <a href="{{ route('tablero.show', [$curso, 'nivel' => $nivel]) }}"
                   class="chip {{ $nivel }} {{ $filtro === $nivel ? 'activo' : '' }}">{{ ucfirst($nivel) }}</a>
            @endforeach
        </div>

        {{-- HU-14 --}}
        <a href="{{ route('tablero.exportar', [$curso, 'nivel' => $filtro]) }}" class="boton contorno">
            Exportar listado (CSV)
        </a>
    </div>

    <table class="tabla">
        <thead>
            <tr>
                <th>Legajo</th>
                <th>Estudiante</th>
                <th>Probabilidad de riesgo</th>
                <th>Nivel</th>
                <th>Asistencia</th>
                <th>Calificaciones</th>
                <th>Entregas</th>
                <th>Participación</th>
                <th><span class="detalle">Detalle</span></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($lista as $estimacion)
                <tr>
                    <td>{{ $estimacion->inscripcion->estudiante->legajo }}</td>
                    <td>{{ $estimacion->inscripcion->estudiante->apellido }}, {{ $estimacion->inscripcion->estudiante->nombre }}</td>
                    <td>
                        <div class="probabilidad">
                            <span class="pista" aria-hidden="true">
                                <span class="relleno" style="width: {{ round($estimacion->probabilidad * 100) }}%"></span>
                            </span>
                            <strong>{{ $decimal($estimacion->probabilidad) }}</strong>
                        </div>
                    </td>
                    <td><span class="etiqueta {{ $estimacion->nivel }}">{{ ucfirst($estimacion->nivel) }}</span></td>
                    <td>{{ $decimal($estimacion->asistencia) }}</td>
                    <td>{{ $decimal($estimacion->calificaciones) }}</td>
                    <td>{{ $decimal($estimacion->entregas) }}</td>
                    <td>{{ $decimal($estimacion->participacion) }}</td>
                    <td class="acciones">
                        <a href="{{ route('tablero.estudiante', [$curso, $estimacion->inscripcion]) }}" class="ver">Ver detalle</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="9">No hay estudiantes en este nivel.</td></tr>
            @endforelse
        </tbody>
    </table>

    <p class="nota">
        Se muestran {{ $lista->count() }} de {{ $total }} estudiantes, ordenados de mayor a menor probabilidad.
        Los indicadores se expresan de 0 a 1. La probabilidad orienta la priorización: no es un diagnóstico.
    </p>
</div>
@endsection