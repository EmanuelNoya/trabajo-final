@extends('layouts.app')

@section('titulo', 'Incorporar datos · ' . $curso->asignatura)

@php
    $estados = ['incorporado' => 'Incorporado', 'observaciones' => 'Con observaciones', 'pendiente' => 'Pendiente'];
    $decimal = fn ($v) => number_format((float) $v, 2, ',', '');
@endphp

@section('contenido')
<div class="encabezado">
    <div>
        <h1>Incorporar datos del curso</h1>
        <p class="subtitulo">
            {{ $curso->asignatura }} · Comisión {{ $curso->comision }} · {{ $curso->periodo_lectivo }} ·
            {{ $inscripciones->count() }} estudiantes en la nómina
        </p>
        <p class="nota">
            {{ $curso->carrera->nombre }} · Docente: {{ $curso->docente->apellido }}, {{ $curso->docente->nombre }} ·
            Del {{ $curso->fecha_inicio->format('d/m/Y') }} al {{ $curso->fecha_fin->format('d/m/Y') }}
            ({{ $curso->cantidadSemanas() }} semanas) · {{ $curso->modalidad }}
        </p>
    </div>
</div>

@include('partials.mensajes')

{{-- Los cuatro archivos requeridos --}}
<div class="tarjeta sin-relleno">
    <table class="tabla">
        <thead>
            <tr>
                <th>Archivo</th>
                <th>Contenido esperado</th>
                <th>Última incorporación</th>
                <th>Estado</th>
                <th><span class="detalle">Acción</span></th>
            </tr>
        </thead>
        <tbody>
            @foreach ($archivos as $tipo => $archivo)
                @php $sinNomina = $tipo !== 'nomina' && $inscripciones->isEmpty(); @endphp
                <tr>
                    <td class="archivo-nombre">{{ $archivo['nombre'] }}</td>
                    <td><span class="codigo">{{ $archivo['contenido'] }}</span></td>
                    <td>
                        @if ($archivo['ultima'])
                            {{ $archivo['ultima']->created_at->format('d/m/Y') }}
                            <div class="detalle">{{ $archivo['detalle'] }}</div>
                        @else
                            —
                        @endif
                    </td>
                    <td><span class="etiqueta {{ $archivo['estado'] }}">{{ $estados[$archivo['estado']] }}</span></td>
                    <td class="acciones">
                        @if ($sinNomina)
                            <span class="boton contorno deshabilitado" title="Primero incorporá la nómina del curso.">
                                {{ $archivo['accion'] }}
                            </span>
                        @else
                            {{-- Al elegir el archivo, el formulario se envía solo --}}
                            <form method="POST" action="{{ $archivo['ruta'] }}" enctype="multipart/form-data" class="form-archivo">
                                @csrf
                                <label class="boton {{ $archivo['ultima'] ? 'contorno' : '' }}">
                                    {{ $archivo['accion'] }}
                                    <input type="file" name="archivo" accept=".csv" class="oculto"
                                           onchange="this.form.submit()">
                                </label>
                            </form>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <p class="nota">
        Archivos CSV con fila de encabezados, separados por ; o ,. Al elegir un archivo se incorpora de inmediato.
        Si trae semanas o evaluaciones ya incorporadas, se pide confirmar el reemplazo.
    </p>
</div>

{{-- Resultado de la última incorporación --}}
@if (session('resultado'))
    @php $resultado = session('resultado'); @endphp
    <div class="tarjeta seccion">
        <h2>Resultado de la última incorporación: {{ $resultado['archivo'] }}</h2>

        <div class="resumen">
            <div class="dato">
                <span class="numero bajo">{{ $resultado['validas'] }}</span>
                <span class="rotulo">filas válidas</span>
            </div>
            <div class="dato">
                <span class="numero alto">{{ $resultado['rechazadas'] }}</span>
                <span class="rotulo">filas rechazadas</span>
            </div>
            @if ($resultado['extra'])
                <div class="dato">
                    <span class="numero">{{ $resultado['extra'][0] }}</span>
                    <span class="rotulo">{{ $resultado['extra'][1] }}</span>
                </div>
            @endif
        </div>

        @if (session('rechazadas'))
            <table class="tabla seccion">
                <thead>
                    <tr><th>Fila</th><th>Legajo</th><th>Motivo del rechazo</th></tr>
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
            <p class="nota">Las filas rechazadas no se almacenan. Corregí el archivo y volvé a incorporarlo para completar los registros.</p>
        @endif
    </div>
@endif

{{-- Estimación --}}
<div class="estimar">
    <form method="POST" action="{{ route('cursos.estimaciones.store', $curso) }}">
        @csrf
        <button type="submit" class="boton" @disabled($pendientes !== [])>Calcular indicadores y estimar riesgo</button>
    </form>
    <p class="nota">
        @if ($pendientes === [])
            Los datos están completos. Cada estimación queda registrada y las anteriores se conservan.
        @else
            Disponible al alcanzar el 30 % del curso (semana {{ $semanaCorte }}) con los cuatro archivos incorporados.
            Falta: {{ implode(' ', $pendientes) }}
        @endif
        <a href="{{ route('cursos.indicadores', $curso) }}">Ver indicadores al corte</a>
    </p>
</div>

{{-- Detalle de lo incorporado --}}
<div class="tarjeta seccion">
    <h2>Datos incorporados</h2>

    <details class="desplegable">
        <summary>Estudiantes de la nómina ({{ $inscripciones->count() }})</summary>
        <table class="tabla seccion">
            <thead>
                <tr>
                    <th>Legajo</th><th>Apellido y nombre</th><th>Documento</th>
                    <th>Semanas con actividad</th><th>Intervenciones en foros</th><th>Estado</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($inscripciones as $inscripcion)
                    <tr>
                        <td>{{ $inscripcion->estudiante->legajo }}</td>
                        <td>{{ $inscripcion->estudiante->apellido }}, {{ $inscripcion->estudiante->nombre }}</td>
                        <td>{{ $inscripcion->estudiante->documento }}</td>
                        <td>{{ $semanasActivas[$inscripcion->id] ?? 0 }} de {{ $semanasActividad->count() }}</td>
                        <td>{{ $intervenciones[$inscripcion->id] ?? '—' }}</td>
                        <td>
                            @if ($inscripcion->fecha_baja)
                                <span class="etiqueta inactiva">Baja {{ $inscripcion->fecha_baja->format('d/m/Y') }}</span>
                            @else
                                <span class="etiqueta activa">Cursando</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6">Sin estudiantes: incorporá la nómina.</td></tr>
                @endforelse
            </tbody>
        </table>
    </details>

    <details class="desplegable">
        <summary>Evaluaciones ({{ $evaluaciones->count() }})</summary>
        <table class="tabla seccion">
            <thead>
                <tr><th>Evaluación</th><th>Vencimiento</th><th>En plazo</th><th>Fuera de plazo</th><th>No presentadas</th><th>Promedio</th></tr>
            </thead>
            <tbody>
                @forelse ($evaluaciones as $evaluacion)
                    <tr>
                        <td>{{ $evaluacion->denominacion }} <div class="detalle">{{ $evaluacion->tipoLegible() }}</div></td>
                        <td>{{ $evaluacion->fecha_vencimiento->format('d/m/Y') }}</td>
                        <td>{{ $evaluacion->en_plazo }}</td>
                        <td>{{ $evaluacion->fuera_de_plazo }}</td>
                        <td>{{ $evaluacion->no_presentadas }}</td>
                        <td>{{ $evaluacion->promedio !== null ? $decimal($evaluacion->promedio) : '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6">Sin evaluaciones incorporadas.</td></tr>
                @endforelse
            </tbody>
        </table>
    </details>

    <details class="desplegable">
        <summary>Historial de archivos incorporados ({{ $curso->cargas->count() }})</summary>
        <table class="tabla seccion">
            <thead>
                <tr><th>Fecha</th><th>Tipo</th><th>Archivo</th><th>Válidas</th><th>Rechazadas</th><th>Estado</th><th>Incorporó</th></tr>
            </thead>
            <tbody>
                @forelse ($curso->cargas as $carga)
                    <tr>
                        <td>{{ $carga->created_at->format('d/m/Y H:i') }}</td>
                        <td>{{ $archivos[$carga->tipo_archivo]['nombre'] }}</td>
                        <td>
                            {{ $carga->nombre_archivo }}
                            <div class="detalle codigo" title="{{ $carga->hash_archivo }}">SHA-256 {{ substr($carga->hash_archivo, 0, 16) }}…</div>
                        </td>
                        <td>{{ $carga->filas_validas }}</td>
                        <td>{{ $carga->filas_rechazadas }}</td>
                        <td>
                            <span class="etiqueta {{ $carga->estado === 'vigente' ? 'activa' : 'inactiva' }}">{{ ucfirst($carga->estado) }}</span>
                        </td>
                        <td>{{ $carga->usuario->apellido }}, {{ $carga->usuario->nombre }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7">Todavía no se incorporaron archivos.</td></tr>
                @endforelse
            </tbody>
        </table>
    </details>
</div>
@endsection