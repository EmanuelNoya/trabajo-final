<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Curso extends Model
{
    public const MODALIDADES = ['A distancia', 'Presencial', 'Híbrida'];

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

    public function nominaVigente(): HasOne
    {
        return $this->hasOne(CargaArchivo::class)
            ->where('tipo_archivo', 'nomina')
            ->where('estado', 'vigente');
    }
}