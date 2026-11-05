@extends('layouts.app')

@section('titulo', 'Editar usuario')

@section('contenido')
<div class="tarjeta formulario">
    <h1>Editar usuario</h1>

    @include('partials.mensajes')

    <form method="POST" action="{{ route('usuarios.update', $usuario) }}">
        @csrf
        @method('PUT')

        @include('administrador.usuarios._formulario')

        <p class="ayuda">La contraseña no se modifica desde acá: el usuario la cambia con "¿Olvidaste tu contraseña?".</p>

        <button type="submit" class="boton">Guardar cambios</button>
        <a href="{{ route('usuarios.index') }}" class="cancelar">Cancelar</a>
    </form>
</div>
@endsection