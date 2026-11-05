@extends('layouts.app')

@section('titulo', $titulo)

@section('contenido')
<div class="tarjeta">
    <h1>{{ $titulo }}</h1>
    <p>Pantalla inicial del perfil {{ auth()->user()->rolLegible() }}.</p>
</div>
@endsection