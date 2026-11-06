<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Evaluacion extends Model
{
    public const TIPOS = [
        'trabajo_practico' => 'Trabajo práctico',
        'actividad_practica' => 'Actividad práctica',
        'foro' => 'Foro',
        'examen_parcial' => 'Examen parcial',
        'otro' => 'Otro',
    ];

    protected $table = 'evaluacion';

    public $timestamps = false;

    protected $fillable = ['curso_id', 'carga_id', 'denominacion', 'tipo', 'fecha_vencimiento'];

    protected function casts(): array
    {
        return [
            'fecha_vencimiento' => 'date',
        ];
    }

    public function curso(): BelongsTo
    {
        return $this->belongsTo(Curso::class);
    }

    public function carga(): BelongsTo
    {
        return $this->belongsTo(CargaArchivo::class, 'carga_id');
    }

    public function entregas(): HasMany
    {
        return $this->hasMany(Entrega::class);
    }

    public function tipoLegible(): string
    {
        return self::TIPOS[$this->tipo];
    }

    //Evaluaciones del curso que provienen de cargas vigentes.
    public function scopeVigentesDelCurso(Builder $query, Curso $curso): void
    {
        $query->where('curso_id', $curso->id)
            ->whereHas('carga', fn ($q) => $q->where('estado', 'vigente'));
    }
}