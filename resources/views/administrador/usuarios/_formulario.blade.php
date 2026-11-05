<label for="nombre">Nombre</label>
<input type="text" id="nombre" name="nombre" value="{{ old('nombre', $usuario->nombre) }}"
       required maxlength="60" autofocus>

<label for="apellido">Apellido</label>
<input type="text" id="apellido" name="apellido" value="{{ old('apellido', $usuario->apellido) }}"
       required maxlength="60">

<label for="correo">Correo institucional</label>
<input type="email" id="correo" name="correo" value="{{ old('correo', $usuario->correo) }}"
       required maxlength="120">

<label for="rol">Perfil</label>
<select id="rol" name="rol" required>
    <option value="">Seleccioná un perfil</option>
    @foreach (App\Models\Usuario::ROLES as $valor => $etiqueta)
        <option value="{{ $valor }}" @selected(old('rol', $usuario->rol) === $valor)>{{ $etiqueta }}</option>
    @endforeach
</select>