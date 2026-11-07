@extends('layouts.app')

@section('titulo', 'Umbrales de ' . $carrera->nombre)

@php
    use App\Models\UmbralRiesgo;
    $porcentaje = fn ($v) => round($v * 100, 2);
@endphp

@section('contenido')
<p><a href="{{ route('umbrales.index') }}">← Umbrales de riesgo</a></p>

<div class="encabezado">
    <h1>Umbrales · {{ $carrera->nombre }}</h1>
</div>

@include('partials.mensajes')

<div class="columnas seccion">
    <div class="tarjeta">
        <h2>Nuevos umbrales</h2>
        <p class="ayuda">
            Un estudiante queda en riesgo <strong>alto</strong> si su probabilidad llega al umbral alto,
            y en riesgo <strong>medio</strong> si llega al umbral medio. Los cambios no repiten la estimación:
            reclasifican a los estudiantes en todos los tableros de la carrera.
        </p>

        <form method="POST" action="{{ route('umbrales.store', $carrera) }}">
            @csrf

            <label for="umbral_medio">Riesgo medio desde (%)</label>
            <input type="number" id="umbral_medio" name="umbral_medio" step="0.1"
                   value="{{ old('umbral_medio', $porcentaje($vigente->umbral_medio)) }}" required>

            <label for="umbral_alto">Riesgo alto desde (%)</label>
            <input type="number" id="umbral_alto" name="umbral_alto" step="0.1"
                   value="{{ old('umbral_alto', $porcentaje($vigente->umbral_alto)) }}" required>

            <label for="fecha_desde">Rigen desde</label>
            <input type="date" id="fecha_desde" name="fecha_desde"
                   value="{{ old('fecha_desde', today()->toDateString()) }}" required>

            <button type="submit" class="boton">Guardar umbrales</button>
        </form>
    </div>

    <div class="tarjeta">
        <h2>Vista previa</h2>
        <p class="ayuda">
            {{ $probabilidades->count() }} estudiantes, según la última estimación de cada curso de la carrera.
        </p>

        <div id="aviso-umbrales" class="alerta error" hidden>
            El umbral de riesgo alto tiene que ser mayor que el de riesgo medio.
        </div>

        <table class="tabla">
            <thead>
                <tr><th>Nivel</th><th>Con los umbrales actuales</th><th>Con los nuevos</th></tr>
            </thead>
            <tbody>
                @foreach (UmbralRiesgo::NIVELES as $nivel => $etiqueta)
                    <tr>
                        <td><span class="etiqueta {{ $nivel }}">{{ $etiqueta }}</span></td>
                        <td>{{ $actual[$nivel] }}</td>
                        <td><strong id="nuevo-{{ $nivel }}">{{ $actual[$nivel] }}</strong></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

{{-- Criterio 3 --}}
<div class="tarjeta sin-relleno seccion">
    <h2 class="titulo-tabla">Historial de cambios</h2>
    <table class="tabla">
        <thead>
            <tr><th>Rige desde</th><th>Medio</th><th>Alto</th><th>Realizó el cambio</th><th>Estado</th></tr>
        </thead>
        <tbody>
            @forelse ($historial as $umbral)
                <tr>
                    <td>{{ $umbral->fecha_desde->format('d/m/Y') }}</td>
                    <td>{{ $porcentaje($umbral->umbral_medio) }} %</td>
                    <td>{{ $porcentaje($umbral->umbral_alto) }} %</td>
                    <td>{{ $umbral->usuario->nombre }} {{ $umbral->usuario->apellido }}</td>
                    <td>
                        @if ($umbral->is($vigente))
                            <span class="etiqueta activa">Vigente</span>
                        @elseif ($umbral->fecha_desde->isFuture())
                            <span class="etiqueta medio">Programado</span>
                        @else
                            <span class="etiqueta inactiva">Anterior</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="5">La carrera usa los valores por defecto (40 % y 60 %).</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection

@push('scripts')
    <script>
        // Vista previa (Figura 10): cuenta los estudiantes de cada nivel con los valores que se están escribiendo
        const probabilidades = @json($probabilidades);
        const medio = document.getElementById('umbral_medio');
        const alto = document.getElementById('umbral_alto');
        const aviso = document.getElementById('aviso-umbrales');

        function actualizar() {
            const m = parseFloat(medio.value) / 100;
            const a = parseFloat(alto.value) / 100;

            if (isNaN(m) || isNaN(a) || a <= m) {
                aviso.hidden = false;
                return;
            }

            aviso.hidden = true;
            const conteo = { alto: 0, medio: 0, bajo: 0 };

            // Misma regla que UmbralRiesgo::nivel()
            probabilidades.forEach(p => {
                if (p >= a) conteo.alto++;
                else if (p >= m) conteo.medio++;
                else conteo.bajo++;
            });

            for (const nivel in conteo) {
                document.getElementById('nuevo-' + nivel).textContent = conteo[nivel];
            }
        }

        medio.addEventListener('input', actualizar);
        alto.addEventListener('input', actualizar);
        actualizar();
    </script>
@endpush