@extends('layouts.app')

@section('titulo', 'Umbrales de riesgo')

@php
    use App\Models\UmbralRiesgo;

    $decimal = fn ($v) => number_format((float) $v, 2, ',', '');
    $campo = fn ($v) => round((float) $v, 2); // valor de los campos numéricos (con punto decimal)
    $total = $probabilidades->count();
    $pct = fn ($n) => $total ? number_format($n / $total * 100, 1, ',', '') . ' %' : '—';

    $medio = old('umbral_medio', $campo($vigente->umbral_medio));
    $alto = old('umbral_alto', $campo($vigente->umbral_alto));
@endphp

@section('contenido')
<div class="encabezado">
    <div>
        <h1>Umbrales de riesgo</h1>
        <p class="subtitulo">{{ $carrera->nombre }} · Se aplican a todos los cursos de la carrera</p>
    </div>
</div>

@include('partials.mensajes')

<form method="POST" action="{{ route('umbrales.store', $carrera) }}" id="form-umbrales">
    @csrf

    <div class="tarjeta">
        <h2>Umbrales de probabilidad</h2>

        <label for="umbral_medio">Riesgo medio desde</label>
        <div class="umbral">
            <input type="range" class="medio" id="rango_medio" min="0.01" max="0.99" step="0.01"
                   value="{{ $medio }}" aria-label="Riesgo medio desde">
            <input type="number" id="umbral_medio" name="umbral_medio" min="0.01" max="0.99" step="0.01"
                   value="{{ $medio }}" required>
            <span class="actual">actual: {{ $decimal($vigente->umbral_medio) }}</span>
        </div>

        <label for="umbral_alto">Riesgo alto desde</label>
        <div class="umbral">
            <input type="range" class="alto" id="rango_alto" min="0.01" max="0.99" step="0.01"
                   value="{{ $alto }}" aria-label="Riesgo alto desde">
            <input type="number" id="umbral_alto" name="umbral_alto" min="0.01" max="0.99" step="0.01"
                   value="{{ $alto }}" required>
            <span class="actual">actual: {{ $decimal($vigente->umbral_alto) }}</span>
        </div>

        <label for="fecha_desde">Rigen desde</label>
        <input type="date" id="fecha_desde" name="fecha_desde" class="campo-fecha"
               value="{{ old('fecha_desde', today()->toDateString()) }}" required>

        <div id="aviso-umbrales" class="alerta error" hidden>
            El umbral de riesgo alto tiene que ser mayor que el de riesgo medio.
        </div>

        <p class="nota">
            El umbral de riesgo alto debe ser mayor que el de riesgo medio. Un umbral más bajo detecta más estudiantes
            en riesgo, pero también señala más casos que no lo están.
        </p>
    </div>

    {{-- Vista previa (Figura 10) --}}
    <div class="tarjeta sin-relleno seccion">
        <h2 class="titulo-tabla">Vista previa con las estimaciones vigentes de la carrera ({{ $total }} estudiantes)</h2>
        <table class="tabla">
            <thead>
                <tr>
                    <th>Nivel</th>
                    <th>Umbrales actuales ({{ $decimal($vigente->umbral_medio) }} y {{ $decimal($vigente->umbral_alto) }})</th>
                    <th>Umbrales propuestos (<span id="propuestos"></span>)</th>
                </tr>
            </thead>
            <tbody>
                @foreach (UmbralRiesgo::NIVELES as $nivel => $etiqueta)
                    <tr>
                        <td><span class="etiqueta {{ $nivel }}">{{ ucfirst($nivel) }}</span></td>
                        <td>{{ $actual[$nivel] }} ({{ $pct($actual[$nivel]) }})</td>
                        <td><strong id="nuevo-{{ $nivel }}"></strong></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="botones">
        <button type="submit" class="boton">Guardar umbrales</button>
        <button type="reset" class="boton contorno">Descartar</button>
    </div>
</form>

<p class="nota">
    @if ($ultimo)
        Último cambio: rige desde el {{ $ultimo->fecha_desde->format('d/m/Y') }}, registrado por
        {{ $ultimo->usuario->apellido }}, {{ $ultimo->usuario->nombre }}.
    @else
        La carrera usa los valores por defecto.
    @endif
    Cambiar los umbrales no repite la estimación: solo reclasifica las probabilidades ya calculadas.
</p>

{{-- HU-11, criterio 3 --}}
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
                    <td>{{ $decimal($umbral->umbral_medio) }}</td>
                    <td>{{ $decimal($umbral->umbral_alto) }}</td>
                    <td>{{ $umbral->usuario->apellido }}, {{ $umbral->usuario->nombre }}</td>
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
                <tr><td colspan="5">La carrera usa los valores por defecto (0,40 y 0,60).</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection

@push('scripts')
    <script>
        // Vista previa: cuenta los estudiantes de cada nivel con los umbrales que se están eligiendo
        const probabilidades = @json($probabilidades);
        const total = probabilidades.length;
        const formato = v => v.toFixed(2).replace('.', ',');

        function actualizar() {
            const m = parseFloat(document.getElementById('umbral_medio').value);
            const a = parseFloat(document.getElementById('umbral_alto').value);
            const aviso = document.getElementById('aviso-umbrales');

            if (isNaN(m) || isNaN(a) || a <= m) {
                aviso.hidden = false;
                return;
            }
            aviso.hidden = true;
            document.getElementById('propuestos').textContent = formato(m) + ' y ' + formato(a);

            // Misma regla que UmbralRiesgo::nivel()
            const conteo = { alto: 0, medio: 0, bajo: 0 };
            probabilidades.forEach(p => {
                if (p >= a) conteo.alto++;
                else if (p >= m) conteo.medio++;
                else conteo.bajo++;
            });

            for (const nivel in conteo) {
                const pct = total ? (conteo[nivel] / total * 100).toFixed(1).replace('.', ',') + ' %' : '—';
                document.getElementById('nuevo-' + nivel).textContent = conteo[nivel] + ' (' + pct + ')';
            }
        }

        // La barra y el número se mantienen sincronizados
        [['rango_medio', 'umbral_medio'], ['rango_alto', 'umbral_alto']].forEach(([idBarra, idNumero]) => {
            const barra = document.getElementById(idBarra);
            const numero = document.getElementById(idNumero);
            barra.addEventListener('input', () => { numero.value = barra.value; actualizar(); });
            numero.addEventListener('input', () => { barra.value = numero.value; actualizar(); });
        });

        // "Descartar" vuelve a los valores iniciales y actualiza la vista previa
        document.getElementById('form-umbrales').addEventListener('reset', () => setTimeout(actualizar));

        actualizar();
    </script>
@endpush