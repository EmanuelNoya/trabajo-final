@extends('layouts.app')

@section('titulo', 'Nuevo usuario')

@section('contenido')
<div class="tarjeta formulario">
    <h1>Nuevo usuario</h1>

    @include('partials.mensajes')

    <form method="POST" action="{{ route('usuarios.store') }}">
        @csrf

        @include('administrador.usuarios._formulario')

        <label for="password">Contraseña inicial</label>
        <input type="password" id="password" name="password" required autocomplete="new-password">

        <label for="password_confirmation">Repetí la contraseña</label>
        <input type="password" id="password_confirmation" name="password_confirmation"
               required autocomplete="new-password">

        <p class="ayuda">Al menos 8 caracteres, con una mayúscula, una minúscula, un número y un símbolo.</p>

        <button type="submit" class="boton">Dar de alta</button>
        <a href="{{ route('usuarios.index') }}" class="cancelar">Cancelar</a>
    </form>
</div>
@endsection