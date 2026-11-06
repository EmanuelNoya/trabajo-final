@extends('layouts.app')

@section('titulo', 'Confirmar reemplazo')

@section('contenido')
<p><a href="{{ route('cursos.show', $curso) }}">← {{ $curso->asignatura }} · Comisión {{ $curso->comision }}</a></p>

<div class="tarjeta formulario">
    <h1>Confirmar el reemplazo</h1>

    <div class="alerta aviso">
        El curso ya tiene {{ $importador->unidad() }} incorporadas que también trae este archivo:
        <strong>{{ implode(', ', $analisis['existentes']) }}</strong>.
        Si confirmás, la carga anterior queda reemplazada y se usan los datos del archivo nuevo.
    </div>

    @if ($analisis['sin_datos'] !== [])
        <div class="alerta error">
            El archivo nuevo no trae: <strong>{{ implode(', ', $analisis['sin_datos']) }}</strong>.
            Si confirmás, quedan sin datos.
        </div>
    @endif

    <dl class="datos">
        <dt>Archivo</dt>   <dd>{{ $pendiente['nombre'] }}</dd>
        <dt>Contenido</dt> <dd>{{ ucfirst($importador->descripcion()) }}</dd>
        <dt>{{ ucfirst($importador->unidad()) }} del archivo</dt> <dd>{{ implode(', ', $analisis['unidades']) }}</dd>
        <dt>Filas válidas</dt>    <dd>{{ count($analisis['validas']) }}</dd>
        <dt>Filas rechazadas</dt> <dd>{{ count($analisis['rechazadas']) }}</dd>
    </dl>

    <div class="botones">
        <form method="POST" action="{{ route('cursos.incorporar.reemplazar', [$curso, $tipo]) }}">
            @csrf
            <button type="submit" class="boton">Confirmar el reemplazo</button>
        </form>
        <form method="POST" action="{{ route('cursos.incorporar.cancelar', [$curso, $tipo]) }}">
            @csrf
            @method('DELETE')
            <button type="submit" class="boton claro">Cancelar</button>
        </form>
    </div>
</div>
@endsection