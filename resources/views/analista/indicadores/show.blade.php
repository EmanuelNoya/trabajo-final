@extends('layouts.app')

@section('titulo', 'Indicadores al corte')

@php
    $formato = fn ($valor) => number_format($valor, 2, ',', '');
@endphp

@section('contenido')
<p><a href="{{ route('cursos.show', $curso) }}">← {{ $curso->asignatura }} · Comisión {{ $curso->comision }}</a></p>

<div class="encabezado">
    <h1>Indicadores al momento de corte</h1>
</div>

@include('partials.mensajes')

<div class="tarjeta">
    <dl class="datos">
        <dt>Período del curso</dt>
        <dd>{{ $curso->fecha_inicio->format('d/m/Y') }} al {{ $curso->fecha_fin->format('d/m/Y') }}</dd>
        <dt>Fecha de corte (30 %)</dt>
        <dd>{{ $corte->format('d/m/Y') }}, inclusive</dd>
        <dt>Semanas consideradas</dt>
        <dd>1 a {{ $semanas }}</dd>
        @if ($resultado)
            <dt>Evaluaciones vencidas</dt>
            <dd>
                {{ $resultado['vencidas']->isEmpty() ? 'Ninguna' : $resultado['vencidas']->pluck('denominacion')->implode(', ') }}
            </dd>
            <dt>Estudiantes</dt>
            <dd>
                {{ $resultado['estudiantes']->count() }}
                @if ($resultado['excluidos'] > 0)
                    ({{ $resultado['excluidos'] }} dados de baja antes del corte no se incluyen)
                @endif
            </dd>
        @endif
    </dl>
</div>

@if ($problemas !== [])
    <div class="alerta error seccion">
        <strong>No se pueden calcular los indicadores:</strong>
        @foreach ($problemas as $problema)
            <div>· {{ $problema }}</div>
        @endforeach
    </div>
@else
    <div class="tarjeta sin-relleno seccion">
        <table class="tabla">
            <thead>
                <tr>
                    <th>Legajo</th>
                    <th>Apellido y nombre</th>
                    <th>Asistencia</th>
                    <th>Calificaciones</th>
                    <th>Entregas</th>
                    <th>Participación</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($resultado['estudiantes'] as $fila)
                    <tr>
                        <td>{{ $fila['inscripcion']->estudiante->legajo }}</td>
                        <td>{{ $fila['inscripcion']->estudiante->apellido }}, {{ $fila['inscripcion']->estudiante->nombre }}</td>
                        <td>{{ $formato($fila['asistencia']) }}</td>
                        <td>
                            {{ $formato($fila['calificaciones']) }}
                            @if ($fila['sin_presentaciones'])
                                <div class="detalle">Sin presentaciones hasta el corte</div>
                            @endif
                        </td>
                        <td>{{ $formato($fila['entregas']) }}</td>
                        <td>{{ $formato($fila['participacion']) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <p class="ayuda seccion">
    Todos los indicadores van de 0 a 1. Se calculan con los registros hasta la fecha de corte inclusive,
    con las mismas definiciones que se usaron para entrenar el modelo.
    </p>

    <div class="tarjeta seccion">
        <h2>Estimación del riesgo</h2>
        @if ($modelo)
            <p class="ayuda">
                Modelo vigente: <strong>{{ $modelo->version }}</strong> ({{ $modelo->algoritmo }}),
                entrenado el {{ $modelo->fecha_entrenamiento->format('d/m/Y') }}.
                Cada ejecución queda registrada; las anteriores se conservan.
            </p>
            <form method="POST" action="{{ route('cursos.estimaciones.store', $curso) }}">
                @csrf
                <button type="submit" class="boton">Ejecutar estimación</button>
            </form>
        @else
            <p class="ayuda">No hay ningún modelo vigente registrado. Registralo con <code>php artisan modelo:registrar</code>.</p>
        @endif
    </div>
@endif
@if ($ejecuciones->isNotEmpty())
    <div class="tarjeta sin-relleno seccion">
        <h2 class="titulo-tabla">Estimaciones realizadas</h2>
        <table class="tabla">
            <thead>
                <tr>
                    <th>Fecha y hora</th>
                    <th>Fecha de corte</th>
                    <th>Modelo</th>
                    <th>Estudiantes</th>
                    <th>Archivos usados</th>
                    <th>Ejecutó</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($ejecuciones as $ejecucion)
                    <tr>
                        <td>
                            {{ $ejecucion->created_at->format('d/m/Y H:i') }}
                            @if ($loop->first)
                                <span class="etiqueta activa">Actual</span>
                            @endif
                        </td>
                        <td>{{ $ejecucion->fecha_corte->format('d/m/Y') }}</td>
                        <td>{{ $ejecucion->modelo->version }}</td>
                        <td>{{ $ejecucion->estimaciones_count }}</td>
                        <td>{{ $ejecucion->cargas_count }}</td>
                        <td>{{ $ejecucion->usuario->nombre }} {{ $ejecucion->usuario->apellido }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
@endsection