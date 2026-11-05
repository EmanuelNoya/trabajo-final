<?php

return [
    'required' => 'El campo :attribute es obligatorio.',
    'email' => 'El campo :attribute debe ser un correo válido.',
    'string' => 'El campo :attribute debe ser texto.',
    'confirmed' => 'La confirmación de la :attribute no coincide.',
    'min' => [
        'string' => 'La :attribute debe tener al menos :min caracteres.',
    ],
    'max' => [
        'string' => 'El campo :attribute no puede superar los :max caracteres.',
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
    ],
];