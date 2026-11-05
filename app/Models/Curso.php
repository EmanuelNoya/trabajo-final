<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Curso extends Model
{
    protected $table = 'curso';

    public $timestamps = false;

    protected $fillable = [
        'carrera_id',
        'docente_id',
        'asignatura',
        'comision',
        'periodo_lectivo',
        'modalidad',
        'fecha_inicio',
        'fecha_fin',
    ];

    protected function casts(): array
    {
        return [
            'fecha_inicio' => 'date',
            'fecha_fin' => 'date',
        ];
    }

    public function carrera(): BelongsTo
    {
        return $this->belongsTo(Carrera::class);
    }

    public function docente(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'docente_id');
    }

    public function inscripciones(): HasMany
    {
        return $this->hasMany(Inscripcion::class);
    }

    public function cargas(): HasMany
    {
        return $this->hasMany(CargaArchivo::class);
    }
}