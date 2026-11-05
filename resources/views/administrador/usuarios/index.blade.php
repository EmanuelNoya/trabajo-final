@extends('layouts.app')

@section('titulo', 'Gestión de usuarios')

@section('contenido')
<div class="encabezado">
    <h1>Gestión de usuarios</h1>
    <a href="{{ route('usuarios.create') }}" class="boton">Nuevo usuario</a>
</div>

@include('partials.mensajes')

<div class="tarjeta sin-relleno">
    <table class="tabla">
        <thead>
            <tr>
                <th>Apellido y nombre</th>
                <th>Correo</th>
                <th>Perfil</th>
                <th>Estado</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @foreach ($usuarios as $usuario)
                <tr>
                    <td>{{ $usuario->apellido }}, {{ $usuario->nombre }}</td>
                    <td>{{ $usuario->correo }}</td>
                    <td>{{ $usuario->rolLegible() }}</td>
                    <td>
                        @if ($usuario->activo)
                            <span class="etiqueta activa">Activo</span>
                        @else
                            <span class="etiqueta inactiva">Desactivado</span>
                            <div class="detalle">
                                {{ $usuario->fecha_desactivacion?->format('d/m/Y H:i') }}
                                · {{ $usuario->desactivadoPor?->nombre }} {{ $usuario->desactivadoPor?->apellido }}
                            </div>
                        @endif
                    </td>
                    <td class="acciones">
                        <a href="{{ route('usuarios.edit', $usuario) }}">Editar</a>

                        @unless ($usuario->is(auth()->user()))
                            @if ($usuario->activo)
                                <form method="POST" action="{{ route('usuarios.desactivar', $usuario) }}"
                                      onsubmit="return confirm(@js('¿Desactivar a ' . $usuario->nombre . ' ' . $usuario->apellido . '? Sus sesiones abiertas se cierran.'))">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="boton chico peligro">Desactivar</button>
                                </form>
                            @else
                                <form method="POST" action="{{ route('usuarios.activar', $usuario) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="boton chico">Reactivar</button>
                                </form>
                            @endif
                        @endunless
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection