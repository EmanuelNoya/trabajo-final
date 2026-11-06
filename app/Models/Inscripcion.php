<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Inscripcion extends Model
{
    protected $table = 'inscripcion';

    public $timestamps = false;

    protected $fillable = ['curso_id', 'estudiante_id', 'carga_id', 'fecha_baja'];

    protected function casts(): array
    {
        return [
            'fecha_baja' => 'date',
        ];
    }

    public function curso(): BelongsTo
    {
        return $this->belongsTo(Curso::class);
    }

    public function estudiante(): BelongsTo
    {
        return $this->belongsTo(Estudiante::class);
    }

    public function carga(): BelongsTo
    {
        return $this->belongsTo(CargaArchivo::class, 'carga_id');
    }

    public function actividades(): HasMany
    {
        return $this->hasMany(ActividadSemanal::class);
    }

    public function entregas(): HasMany
    {
        return $this->hasMany(Entrega::class);
    }

    public function participaciones(): HasMany
    {
        return $this->hasMany(ParticipacionForo::class);
    }
}