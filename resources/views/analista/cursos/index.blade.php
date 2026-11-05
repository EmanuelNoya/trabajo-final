@extends('layouts.app')

@section('titulo', 'Cursos')

@section('contenido')
<div class="encabezado">
    <h1>Cursos</h1>
    <a href="{{ route('cursos.create') }}" class="boton">Nuevo curso</a>
</div>

@include('partials.mensajes')

<div class="tarjeta sin-relleno">
    <table class="tabla">
        <thead>
            <tr>
                <th>Asignatura</th>
                <th>Comisión</th>
                <th>Período</th>
                <th>Docente</th>
                <th>Inscriptos</th>
                <th>Nómina</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($cursos as $curso)
                <tr>
                    <td>
                        <a href="{{ route('cursos.show', $curso) }}">{{ $curso->asignatura }}</a>
                        <div class="detalle">{{ $curso->carrera->nombre }}</div>
                    </td>
                    <td>{{ $curso->comision }}</td>
                    <td>{{ $curso->periodo_lectivo }}</td>
                    <td>{{ $curso->docente->apellido }}, {{ $curso->docente->nombre }}</td>
                    <td>{{ $curso->inscripciones_count }}</td>
                    <td>
                        @if ($curso->nominaVigente)
                            <span class="etiqueta activa">Incorporada</span>
                            <div class="detalle">{{ $curso->nominaVigente->created_at->format('d/m/Y H:i') }}</div>
                        @else
                            <span class="etiqueta inactiva">Sin nómina</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="6">Todavía no hay cursos registrados.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection