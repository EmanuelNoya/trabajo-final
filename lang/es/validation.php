<?php

return [
    'required' => 'El campo :attribute es obligatorio.',
    'email' => 'El campo :attribute debe ser un correo válido.',
    'string' => 'El campo :attribute debe ser texto.',
    'confirmed' => 'La confirmación de la :attribute no coincide.',
    'unique' => 'Ya existe un registro con ese :attribute.',
    'in' => 'El :attribute seleccionado no es válido.',
    'date' => 'El campo :attribute no es una fecha válida.',
    'after' => 'La :attribute debe ser posterior a la :date.',
    'exists' => 'El :attribute seleccionado no es válido.',
    'file' => 'El :attribute debe ser un archivo.',
    'extensions' => 'El :attribute debe tener extensión :values.',
    'uploaded' => 'No se pudo subir el :attribute.',
    'min' => [
        'string' => 'La :attribute debe tener al menos :min caracteres.',
    ],
    'max' => [
        'string' => 'El campo :attribute no puede superar los :max caracteres.',
        'file' => 'El :attribute no puede superar los :max kilobytes.',
    ],
    'password' => [
        'letters' => 'La contraseña debe incluir al menos una letra.',
        'mixed' => 'La contraseña debe incluir al menos una mayúscula y una minúscula.',
        'numbers' => 'La contraseña debe incluir al menos un número.',
        'symbols' => 'La contraseña debe incluir al menos un símbolo.',
        'uncompromised' => 'La contraseña apareció en una filtración de datos. Elegí otra.',
    ],

    'attributes' => [
        'correo' => 'correo',
        'password' => 'contraseña',
        'password_confirmation' => 'confirmación de la contraseña',
        'nombre' => 'nombre',
        'apellido' => 'apellido',
        'rol' => 'perfil',
        'carrera_id' => 'carrera',
        'docente_id' => 'docente',
        'asignatura' => 'asignatura',
        'comision' => 'comisión',
        'periodo_lectivo' => 'período lectivo',
        'modalidad' => 'modalidad',
        'fecha_inicio' => 'fecha de inicio',
        'fecha_fin' => 'fecha de fin',
        'archivo' => 'archivo',
    ],
];