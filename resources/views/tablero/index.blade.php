@extends('layouts.app')

@section('titulo', auth()->user()->rol === 'docente' ? 'Mis cursos' : 'Cursos de la carrera')

@section('contenido')
<div class="encabezado">
    <h1>{{ auth()->user()->rol === 'docente' ? 'Mis cursos' : 'Cursos de la carrera' }}</h1>
</div>

<div class="tarjeta sin-relleno">
    <table class="tabla">
        <thead>
            <tr>
                <th>Asignatura</th>
                <th>Comisión</th>
                <th>Período</th>
                @if (auth()->user()->rol === 'responsable')
                    <th>Docente</th>
                @endif
                <th>Estimación</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($cursos as $curso)
                <tr>
                    <td>
                        <a href="{{ route('tablero.show', $curso) }}">{{ $curso->asignatura }}</a>
                        <div class="detalle">{{ $curso->carrera->nombre }}</div>
                    </td>
                    <td>{{ $curso->comision }}</td>
                    <td>{{ $curso->periodo_lectivo }}</td>
                    @if (auth()->user()->rol === 'responsable')
                        <td>{{ $curso->docente->apellido }}, {{ $curso->docente->nombre }}</td>
                    @endif
                    <td>
                        @if ($curso->ejecucionActual)
                            {{ $curso->ejecucionActual->created_at->format('d/m/Y H:i') }}
                        @else
                            <span class="etiqueta inactiva">Sin estimación</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="5">No tenés cursos asignados.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection