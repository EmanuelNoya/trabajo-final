<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EjecucionEstimacion extends Model
{
    protected $table = 'ejecucion_estimacion';

    protected $fillable = ['curso_id', 'modelo_id', 'usuario_id', 'fecha_corte'];

    protected function casts(): array
    {
        return [
            'fecha_corte' => 'date',
        ];
    }

    public function curso(): BelongsTo
    {
        return $this->belongsTo(Curso::class);
    }

    public function modelo(): BelongsTo
    {
        return $this->belongsTo(Modelo::class);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class);
    }

    /** Cargas vigentes al momento de la ejecución (trazabilidad) */
    public function cargas(): BelongsToMany
    {
        return $this->belongsToMany(CargaArchivo::class, 'ejecucion_carga', 'ejecucion_id', 'carga_id');
    }

    public function estimaciones(): HasMany
    {
        return $this->hasMany(Estimacion::class, 'ejecucion_id');
    }
}