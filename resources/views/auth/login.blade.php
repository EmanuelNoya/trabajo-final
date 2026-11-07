@extends('layouts.app')

@section('titulo', 'Iniciar sesión')

@section('contenido')
<div class="tarjeta login">
    <p class="sobretitulo">{{ config('app.institucion') }}</p>
    <h1 class="titulo-sistema">{{ config('app.name') }}</h1>
    <p class="subtitulo">Ingresá con tu cuenta institucional</p>

    @include('partials.mensajes')

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <label for="correo">Correo electrónico</label>
        <input type="email" id="correo" name="correo" value="{{ old('correo') }}"
               required autofocus autocomplete="username">

        <label for="password">Contraseña</label>
        <input type="password" id="password" name="password"
               required autocomplete="current-password">

        <button type="submit" class="boton ancho">Ingresar</button>
    </form>

    <p class="pie"><a href="{{ route('password.request') }}">¿Olvidaste tu contraseña?</a></p>
    <p class="nota">El acceso a la información de cada estudiante depende del perfil asignado al usuario.</p>
</div>
@endsection