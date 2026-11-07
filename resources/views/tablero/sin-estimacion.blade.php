@extends('layouts.app')

@section('titulo', $curso->asignatura)

@section('contenido')
<p><a href="{{ route('tablero.index') }}">← Cursos</a></p>

<div class="encabezado">
    <h1>{{ $curso->asignatura }} · Comisión {{ $curso->comision }}</h1>
</div>

<div class="tarjeta formulario">
    <h2>Este curso todavía no tiene una estimación del riesgo</h2>

    @if ($pendientes !== [])
        <p>Para estimar el riesgo faltan estos pasos, a cargo del analista de datos:</p>
        <ul>
            @foreach ($pendientes as $pendiente)
                <li>{{ $pendiente }}</li>
            @endforeach
        </ul>
    @else
        <p>Los datos del curso están completos: falta que el analista de datos ejecute la estimación.</p>
    @endif
</div>
@endsection