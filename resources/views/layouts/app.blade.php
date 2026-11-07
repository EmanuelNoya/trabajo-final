<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('titulo') · {{ config('app.name') }}</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
    @auth
        <header class="barra">
            <div class="marca">
                <strong>{{ config('app.name') }}</strong>
                <nav class="menu">
                    @switch(auth()->user()->rol)
                        @case('docente')
                            <a href="{{ route('tablero.index') }}">Mis cursos</a>
                            @break
                        @case('responsable')
                            <a href="{{ route('carrera.estado') }}">Cursos de la carrera</a>
                            <a href="{{ route('umbrales.index') }}">Umbrales</a>
                            <a href="{{ route('modelo.vigente') }}">Modelo</a>
                            @break
                        @case('analista')
                            <a href="{{ route('cursos.index') }}">Cursos</a>
                            @break
                        @case('administrador')
                            <a href="{{ route('usuarios.index') }}">Usuarios</a>
                            @break
                    @endswitch
                </nav>
            </div>
            <div>
                {{ auth()->user()->nombre }} {{ auth()->user()->apellido }} · {{ auth()->user()->rolLegible() }}
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="boton secundario">Cerrar sesión</button>
                </form>
            </div>
        </header>
    @endauth

    <main class="contenido">
        @yield('contenido')
    </main>

    @stack('scripts')
</body>
</html>