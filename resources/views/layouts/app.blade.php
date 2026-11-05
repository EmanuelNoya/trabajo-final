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
            <strong>{{ config('app.name') }}</strong>
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
</body>
</html>