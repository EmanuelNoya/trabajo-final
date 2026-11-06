@extends('layouts.app')

@section('titulo', 'Confirmar reemplazo')

@section('contenido')
<p><a href="{{ route('cursos.show', $curso) }}">← {{ $curso->asignatura }} · Comisión {{ $curso->comision }}</a></p>

<div class="tarjeta formulario">
    <h1>Confirmar el reemplazo</h1>

    <div class="alerta aviso">
        El curso ya tiene actividad incorporada para
        {{ count($analisis['semanas_existentes']) === 1 ? 'la semana' : 'las semanas' }}
        <strong>{{ implode(', ', $analisis['semanas_existentes']) }}</strong>.
        Si confirmás, la carga anterior queda reemplazada y esas semanas se toman del archivo nuevo.
    </div>

    @if ($analisis['semanas_sin_datos'] !== [])
        <div class="alerta error">
            El archivo nuevo no trae
            {{ count($analisis['semanas_sin_datos']) === 1 ? 'la semana' : 'las semanas' }}
            <strong>{{ implode(', ', $analisis['semanas_sin_datos']) }}</strong>.
            Si confirmás, quedan sin datos de actividad.
        </div>
    @endif

    <dl class="datos">
        <dt>Archivo</dt>             <dd>{{ $pendiente['nombre'] }}</dd>
        <dt>Semanas del archivo</dt> <dd>{{ implode(', ', $analisis['semanas']) }}</dd>
        <dt>Filas válidas</dt>       <dd>{{ count($analisis['validas']) }}</dd>
        <dt>Filas rechazadas</dt>    <dd>{{ count($analisis['rechazadas']) }}</dd>
    </dl>

    <div class="botones">
        <form method="POST" action="{{ route('cursos.actividad.reemplazar', $curso) }}">
            @csrf
            <button type="submit" class="boton">Confirmar el reemplazo</button>
        </form>
        <form method="POST" action="{{ route('cursos.actividad.cancelar', $curso) }}">
            @csrf
            @method('DELETE')
            <button type="submit" class="boton claro">Cancelar</button>
        </form>
    </div>
</div>
@endsection