@extends('layouts.app')

@section('titulo', $curso->asignatura)

@php
    $porcentaje = fn ($valor) => number_format($valor * 100, 1, ',', '') . ' %';
    $indicador = fn ($valor) => number_format($valor, 2, ',', '');
@endphp

@section('contenido')
<p><a href="{{ route(auth()->user()->rol === 'responsable' ? 'carrera.estado' : 'tablero.index') }}">← Cursos</a></p>

<div class="encabezado">
    <h1>{{ $curso->asignatura }} · Comisión {{ $curso->comision }}</h1>
    <a href="{{ route('tablero.exportar', [$curso, 'nivel' => $filtro]) }}" class="boton">
        Exportar CSV{{ $filtro ? ' (' . App\Models\UmbralRiesgo::NIVELES[$filtro] . ')' : '' }}
    </a>
</div>

<p class="ayuda">
    Estimación del {{ $ejecucion->created_at->format('d/m/Y H:i') }},
    con los registros hasta el {{ $ejecucion->fecha_corte->format('d/m/Y') }} (30 % del curso).
    Modelo {{ $ejecucion->modelo->version }}.
    Umbrales de la carrera: medio desde {{ $porcentaje($umbral->umbral_medio) }},
    alto desde {{ $porcentaje($umbral->umbral_alto) }}.
</p>

{{-- Criterio 1: cantidad por nivel; cada tarjeta filtra (criterio 2) --}}
<div class="niveles">
    @foreach (App\Models\UmbralRiesgo::NIVELES as $nivel => $etiqueta)
        <a href="{{ route('tablero.show', [$curso, 'nivel' => $nivel]) }}"
           class="nivel nivel-{{ $nivel }} {{ $filtro === $nivel ? 'seleccionado' : '' }}">
            <span class="cantidad">{{ $conteo[$nivel] }}</span>
            <span>{{ $etiqueta }}</span>
        </a>
    @endforeach
</div>

@if ($filtro)
    <p class="seccion">
        Mostrando solo: <strong>{{ App\Models\UmbralRiesgo::NIVELES[$filtro] }}</strong> ·
        <a href="{{ route('tablero.show', $curso) }}">Ver todos</a>
    </p>
@endif

<div class="tarjeta sin-relleno seccion">
    <table class="tabla">
        <thead>
            <tr>
                <th>Legajo</th>
                <th>Apellido y nombre</th>
                <th>Probabilidad</th>
                <th>Nivel</th>
                <th>Asistencia</th>
                <th>Calificaciones</th>
                <th>Entregas</th>
                <th>Participación</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($lista as $estimacion)
                <tr>
                    <td>{{ $estimacion->inscripcion->estudiante->legajo }}</td>
                    <td>
                        <a href="{{ route('tablero.estudiante', [$curso, $estimacion->inscripcion]) }}">
                            {{ $estimacion->inscripcion->estudiante->apellido }}, {{ $estimacion->inscripcion->estudiante->nombre }}
                        </a>
                    </td>
                    <td><strong>{{ $porcentaje($estimacion->probabilidad) }}</strong></td>
                    <td><span class="etiqueta {{ $estimacion->nivel }}">{{ App\Models\UmbralRiesgo::NIVELES[$estimacion->nivel] }}</span></td>
                    <td>{{ $indicador($estimacion->asistencia) }}</td>
                    <td>{{ $indicador($estimacion->calificaciones) }}</td>
                    <td>{{ $indicador($estimacion->entregas) }}</td>
                    <td>{{ $indicador($estimacion->participacion) }}</td>
                </tr>
            @empty
                <tr><td colspan="8">No hay estudiantes en este nivel.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<p class="ayuda seccion">
    Los indicadores van de 0 a 1 y se calcularon al momento de corte. La probabilidad es una estimación
    para priorizar el acompañamiento, no un diagnóstico.
</p>
@endsection