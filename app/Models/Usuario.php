<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class Usuario extends Authenticatable
{
    protected $table = 'usuario';

    protected $fillable = [
        'nombre',
        'apellido',
        'correo',
        'hash_contrasena',
        'rol',
        'activo',
    ];

    protected $hidden = [
        'hash_contrasena',
    ];

    protected function casts(): array
    {
        return [
            'hash_contrasena' => 'hashed',
            'activo' => 'boolean',
        ];
    }


    public function getAuthPasswordName(): string
    {
        return 'hash_contrasena';
    }
}