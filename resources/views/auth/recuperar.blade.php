@extends('layouts.app')

@section('titulo', 'Recuperar contraseña')

@section('contenido')
<div class="tarjeta login">
    <h1>Recuperar contraseña</h1>

    @include('partials.mensajes')

    <p class="ayuda">Ingresá tu correo institucional y te enviamos un enlace para elegir una contraseña nueva.</p>

    <form method="POST" action="{{ route('password.email') }}">
        @csrf

        <label for="correo">Correo institucional</label>
        <input type="email" id="correo" name="correo" value="{{ old('correo') }}"
               required autofocus autocomplete="username">

        <button type="submit" class="boton ancho">Enviar enlace</button>
    </form>

    <p class="pie"><a href="{{ route('login') }}">Volver a iniciar sesión</a></p>
</div>
@endsection