@extends('layouts.app')

@section('titulo', 'Elegir contraseña nueva')

@section('contenido')
<div class="tarjeta login">
    <h1>Elegir contraseña nueva</h1>

    @include('partials.mensajes')

    <form method="POST" action="{{ route('password.store') }}">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">

        <label for="correo">Correo institucional</label>
        <input type="email" id="correo" name="correo" value="{{ old('correo', $correo) }}"
               required autocomplete="username">

        <label for="password">Contraseña nueva</label>
        <input type="password" id="password" name="password"
               required autofocus autocomplete="new-password">

        <label for="password_confirmation">Repetí la contraseña</label>
        <input type="password" id="password_confirmation" name="password_confirmation"
               required autocomplete="new-password">

        <p class="ayuda">Al menos 8 caracteres, con una mayúscula, una minúscula, un número y un símbolo.</p>

        <button type="submit" class="boton ancho">Guardar contraseña</button>
    </form>
</div>
@endsection