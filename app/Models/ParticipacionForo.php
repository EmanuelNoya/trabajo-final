<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ParticipacionForo extends Model
{
    protected $table = 'participacion_foro';

    public $timestamps = false;

    protected $fillable = ['inscripcion_id', 'carga_id', 'semana', 'intervenciones'];

    protected function casts(): array
    {
        return [
            'semana' => 'integer',
            'intervenciones' => 'integer',
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

    /**
     * Registros de un curso que provienen de cargas vigentes.
     */
    public function scopeVigentesDelCurso(Builder $query, Curso $curso): void
    {
        $query->whereHas('carga', fn ($q) => $q
            ->where('curso_id', $curso->id)
            ->where('estado', 'vigente'));
    }
}