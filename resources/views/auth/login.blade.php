@extends('layouts.app')

@section('titulo', 'Iniciar sesión')

@section('contenido')
<div class="tarjeta login">
    <h1>Iniciar sesión</h1>

    @if (session('estado'))
        <div class="alerta info">{{ session('estado') }}</div>
    @endif

    @if ($errors->any())
        <div class="alerta error">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <label for="correo">Correo institucional</label>
        <input type="email" id="correo" name="correo" value="{{ old('correo') }}"
               required autofocus autocomplete="username">

        <label for="password">Contraseña</label>
        <input type="password" id="password" name="password"
               required autocomplete="current-password">

        <button type="submit" class="boton ancho">Ingresar</button>
    </form>
</div>
@endsection