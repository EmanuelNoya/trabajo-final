@extends('layouts.app')

@section('titulo', 'Umbrales de riesgo')

@section('contenido')
<div class="encabezado">
    <h1>Umbrales de riesgo</h1>
</div>

<div class="tarjeta sin-relleno">
    <table class="tabla">
        <thead>
            <tr><th>Carrera</th><th>Riesgo medio desde</th><th>Riesgo alto desde</th><th>Rige desde</th><th></th></tr>
        </thead>
        <tbody>
            @forelse ($carreras as $carrera)
                <tr>
                    <td>{{ $carrera->nombre }}</td>
                    <td>{{ round($carrera->vigente->umbral_medio * 100, 2) }} %</td>
                    <td>{{ round($carrera->vigente->umbral_alto * 100, 2) }} %</td>
                    <td>{{ $carrera->vigente->fecha_desde?->format('d/m/Y') ?? 'Valores por defecto' }}</td>
                    <td class="acciones"><a href="{{ route('umbrales.edit', $carrera) }}">Configurar</a></td>
                </tr>
            @empty
                <tr><td colspan="5">No sos responsable de ninguna carrera.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection