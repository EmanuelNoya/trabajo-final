@extends('layouts.app')

@section('titulo', 'Nuevo curso')

@section('contenido')
<div class="tarjeta formulario">
    <h1>Nuevo curso</h1>

    @include('partials.mensajes')

    <form method="POST" action="{{ route('cursos.store') }}">
        @csrf

        <label for="carrera_id">Carrera</label>
        <select id="carrera_id" name="carrera_id" required>
            <option value="">Seleccioná una carrera</option>
            @foreach ($carreras as $carrera)
                <option value="{{ $carrera->id }}" @selected(old('carrera_id') == $carrera->id)>{{ $carrera->nombre }}</option>
            @endforeach
        </select>

        <label for="asignatura">Asignatura</label>
        <input type="text" id="asignatura" name="asignatura" value="{{ old('asignatura') }}" required maxlength="120">

        <label for="comision">Comisión</label>
        <input type="text" id="comision" name="comision" value="{{ old('comision') }}" required maxlength="10">

        <label for="periodo_lectivo">Período lectivo</label>
        <input type="text" id="periodo_lectivo" name="periodo_lectivo" value="{{ old('periodo_lectivo') }}"
               required maxlength="20" placeholder="Por ejemplo: 2026-2C">

        <label for="modalidad">Modalidad</label>
        <select id="modalidad" name="modalidad" required>
            @foreach (App\Models\Curso::MODALIDADES as $modalidad)
                <option value="{{ $modalidad }}" @selected(old('modalidad', 'A distancia') === $modalidad)>{{ $modalidad }}</option>
            @endforeach
        </select>

        <label for="fecha_inicio">Fecha de inicio</label>
        <input type="date" id="fecha_inicio" name="fecha_inicio" value="{{ old('fecha_inicio') }}" required>

        <label for="fecha_fin">Fecha de fin</label>
        <input type="date" id="fecha_fin" name="fecha_fin" value="{{ old('fecha_fin') }}" required>

        <label for="docente_id">Docente a cargo</label>
        <select id="docente_id" name="docente_id" required>
            <option value="">Seleccioná un docente</option>
            @foreach ($docentes as $docente)
                <option value="{{ $docente->id }}" @selected(old('docente_id') == $docente->id)>
                    {{ $docente->apellido }}, {{ $docente->nombre }}
                </option>
            @endforeach
        </select>

        <button type="submit" class="boton">Registrar curso</button>
        <a href="{{ route('cursos.index') }}" class="cancelar">Cancelar</a>
    </form>
</div>
@endsection