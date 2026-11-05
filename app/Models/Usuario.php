<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    public function cursosDictados(): HasMany
    {
        return $this->hasMany(Curso::class, 'docente_id');
    }

    public function rolLegible(): string
    {
        return match ($this->rol) {
            'docente' => 'Docente',
            'responsable' => 'Responsable académico',
            'analista' => 'Analista de datos',
            'administrador' => 'Administrador',
        };
    }

}