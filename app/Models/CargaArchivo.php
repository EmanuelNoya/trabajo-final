<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CargaArchivo extends Model
{
    protected $table = 'carga_archivo';

    protected $fillable = [
        'curso_id',
        'usuario_id',
        'tipo_archivo',
        'nombre_archivo',
        'hash_archivo',
        'filas_validas',
        'filas_rechazadas',
        'estado',
    ];

    protected function casts(): array
    {
        return [
            'filas_validas' => 'integer',
            'filas_rechazadas' => 'integer',
        ];
    }

    public function curso(): BelongsTo
    {
        return $this->belongsTo(Curso::class);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class);
    }

    public function inscripciones(): HasMany
    {
        return $this->hasMany(Inscripcion::class, 'carga_id');
    }
}