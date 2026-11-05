<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Notifications\Notifiable;

class Usuario extends Authenticatable
{
    use Notifiable;

    public const ROLES = [
        'docente' => 'Docente',
        'responsable' => 'Responsable académico',
        'analista' => 'Analista de datos',
        'administrador' => 'Administrador',
    ];

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
            'fecha_desactivacion' => 'datetime'
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

    public function desactivadoPor(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'desactivado_por');
    }

    public function rolLegible(): string
    {
        return self::ROLES[$this->rol];
    }

    public function getEmailForPasswordReset(): string
    {
        return $this->correo;
    }

    public function routeNotificationForMail(): string
    {
        return $this->correo;
    }

}