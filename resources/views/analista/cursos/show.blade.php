@extends('layouts.app')

@section('titulo', $curso->asignatura)

@section('contenido')
<p><a href="{{ route('cursos.index') }}">← Cursos</a></p>

<div class="encabezado">
    <h1>{{ $curso->asignatura }} · Comisión {{ $curso->comision }}</h1>
</div>

@include('partials.mensajes')

<div class="tarjeta">
    <dl class="datos">
        <dt>Carrera</dt>         <dd>{{ $curso->carrera->nombre }}</dd>
        <dt>Período lectivo</dt> <dd>{{ $curso->periodo_lectivo }}</dd>
        <dt>Modalidad</dt>       <dd>{{ $curso->modalidad }}</dd>
        <dt>Fechas</dt>          <dd>{{ $curso->fecha_inicio->format('d/m/Y') }} al {{ $curso->fecha_fin->format('d/m/Y') }}</dd>
        <dt>Docente</dt>         <dd>{{ $curso->docente->apellido }}, {{ $curso->docente->nombre }}</dd>
    </dl>
</div>

<div class="tarjeta seccion">
    <h2>Incorporar nómina</h2>
    <p class="ayuda">
        Archivo CSV con encabezados. Columnas obligatorias: <strong>legajo, apellido, nombre</strong>.
        Opcionales: <strong>documento, fecha_baja</strong> (AAAA-MM-DD o DD/MM/AAAA). Separador ; o ,.
        Si el curso ya tiene nómina, la nueva la reemplaza.
    </p>

    <form method="POST" action="{{ route('cursos.nomina', $curso) }}" enctype="multipart/form-data">
        @csrf
        <input type="file" name="archivo" accept=".csv" required>
        <button type="submit" class="boton">Incorporar</button>
    </form>
</div>

<div class="tarjeta seccion">
    <h2>Incorporar actividad semanal</h2>

    @if ($inscripciones->isEmpty())
        <p class="ayuda">Primero incorporá la nómina del curso.</p>
    @else
        <p class="ayuda">
            Archivo CSV con las columnas <strong>legajo, semana, con_actividad</strong>
            (1 o 0; también sí o no), con una fila por estudiante y semana.
            El curso tiene {{ $curso->cantidadSemanas() }} semanas.
            @if ($semanasActividad->isNotEmpty())
                Semanas ya incorporadas: <strong>{{ $semanasActividad->implode(', ') }}</strong>.
            @endif
        </p>

        <form method="POST" action="{{ route('cursos.actividad', $curso) }}" enctype="multipart/form-data">
            @csrf
            <input type="file" name="archivo" accept=".csv" required>
            <button type="submit" class="boton">Incorporar</button>
        </form>
    @endif
</div>

@if (session('rechazadas'))
    <div class="tarjeta sin-relleno seccion">
        <h2 class="titulo-tabla">Filas rechazadas</h2>
        <table class="tabla">
            <thead>
                <tr><th>Fila</th><th>Legajo</th><th>Motivo</th></tr>
            </thead>
            <tbody>
                @foreach (session('rechazadas') as $rechazada)
                    <tr>
                        <td>{{ $rechazada['fila'] }}</td>
                        <td>{{ $rechazada['legajo'] }}</td>
                        <td>{{ $rechazada['motivo'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif

<div class="tarjeta sin-relleno seccion">
    <h2 class="titulo-tabla">Estudiantes inscriptos ({{ $inscripciones->count() }})</h2>
    <table class="tabla">
        <thead>
            <tr><th>Legajo</th><th>Apellido y nombre</th><th>Documento</th><th>Estado</th></tr>
        </thead>
        <tbody>
            @forelse ($inscripciones as $inscripcion)
                <tr>
                    <td>{{ $inscripcion->estudiante->legajo }}</td>
                    <td>{{ $inscripcion->estudiante->apellido }}, {{ $inscripcion->estudiante->nombre }}</td>
                    <td>{{ $inscripcion->estudiante->documento }}</td>
                    <td>
                        @if ($inscripcion->fecha_baja)
                            <span class="etiqueta inactiva">Baja {{ $inscripcion->fecha_baja->format('d/m/Y') }}</span>
                        @else
                            <span class="etiqueta activa">Cursando</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="4">Sin estudiantes: incorporá la nómina.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="tarjeta sin-relleno seccion">
    <h2 class="titulo-tabla">Archivos incorporados</h2>
    <table class="tabla">
        <thead>
            <tr><th>Fecha</th><th>Tipo</th><th>Archivo</th><th>Válidas</th><th>Rechazadas</th><th>Estado</th><th>Incorporó</th></tr>
        </thead>
        <tbody>
            @forelse ($curso->cargas as $carga)
                <tr>
                    <td>{{ $carga->created_at->format('d/m/Y H:i') }}</td>
                    <td>{{ ucfirst($carga->tipo_archivo) }}</td>
                    <td>
                        {{ $carga->nombre_archivo }}
                        <div class="detalle codigo" title="{{ $carga->hash_archivo }}">SHA-256 {{ substr($carga->hash_archivo, 0, 16) }}…</div>
                    </td>
                    <td>{{ $carga->filas_validas }}</td>
                    <td>{{ $carga->filas_rechazadas }}</td>
                    <td>
                        <span class="etiqueta {{ $carga->estado === 'vigente' ? 'activa' : 'inactiva' }}">{{ ucfirst($carga->estado) }}</span>
                    </td>
                    <td>{{ $carga->usuario->nombre }} {{ $carga->usuario->apellido }}</td>
                </tr>
            @empty
                <tr><td colspan="7">Todavía no se incorporaron archivos.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection