<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActividadSemanal extends Model
{
    protected $table = 'actividad_semanal';

    public $timestamps = false;

    protected $fillable = ['inscripcion_id', 'carga_id', 'semana', 'con_actividad'];

    protected function casts(): array
    {
        return [
            'semana' => 'integer',
            'con_actividad' => 'boolean',
        ];
    }

    public function inscripcion(): BelongsTo
    {
        return $this->belongsTo(Inscripcion::class);
    }

    public function carga(): BelongsTo
    {
        return $this->belongsTo(CargaArchivo::class, 'carga_id');
    }

    public function scopeVigentesDelCurso(Builder $query, Curso $curso): void
    {
        $query->whereHas('carga', fn ($q) => $q
            ->where('curso_id', $curso->id)
            ->where('estado', 'vigente'));
    }
}