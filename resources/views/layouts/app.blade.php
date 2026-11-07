<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('titulo') · {{ config('app.name') }}</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body class="{{ auth()->check() ? '' : 'acceso' }}">
    @auth
        @php
            $yo = auth()->user();
            // Las vistas de un curso reciben la variable $curso: con ella se arman las opciones del curso
            $cursoActual = isset($curso) && $curso instanceof App\Models\Curso ? $curso : null;

            $enlaces = match ($yo->rol) {
                'docente' => array_filter([
                    ['Mis cursos', route('tablero.index'), request()->routeIs('tablero.index')],
                    $cursoActual ? ['Tablero del curso', route('tablero.show', $cursoActual), request()->routeIs('tablero.show', 'tablero.estudiante')] : null,
                ]),
                'responsable' => array_filter([
                    ['Cursos de la carrera', route('carrera.estado'), request()->routeIs('carrera.estado')],
                    $cursoActual ? ['Tablero del curso', route('tablero.show', $cursoActual), request()->routeIs('tablero.show', 'tablero.estudiante')] : null,
                    ['Umbrales de riesgo', route('umbrales.index'), request()->routeIs('umbrales.*')],
                    ['Importancia de indicadores', route('modelo.vigente'), request()->routeIs('modelo.vigente')],
                ]),
                'analista' => array_filter([
                    ['Cursos', route('cursos.index'), request()->routeIs('cursos.index', 'cursos.create')],
                    $cursoActual ? ['Incorporar datos', route('cursos.show', $cursoActual), request()->routeIs('cursos.show', 'cursos.incorporar.*')] : null,
                    $cursoActual ? ['Indicadores y estimación', route('cursos.indicadores', $cursoActual), request()->routeIs('cursos.indicadores')] : null,
                ]),
                'administrador' => [
                    ['Usuarios', route('usuarios.index'), request()->routeIs('usuarios.*')],
                ],
                default => [],
            };
        @endphp

        <header class="cabecera">
            <span class="sistema">{{ config('app.name') }}</span>
            <div class="usuario">
                <span>{{ $yo->apellido }}, {{ $yo->nombre }} · {{ $yo->rolLegible() }}</span>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="salir">Cerrar sesión</button>
                </form>
            </div>
        </header>

        <div class="aplicacion">
            <nav class="lateral" aria-label="Menú principal">
                @foreach ($enlaces as [$texto, $url, $activo])
                    <a href="{{ $url }}" class="{{ $activo ? 'activo' : '' }}" @if ($activo) aria-current="page" @endif>{{ $texto }}</a>
                @endforeach
            </nav>

            <main class="contenido">
                @yield('contenido')
            </main>
        </div>
    @else
        <main>
            @yield('contenido')
        </main>
    @endauth

    @stack('scripts')
</body>
</html>