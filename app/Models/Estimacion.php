<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Estimacion extends Model
{
    protected $table = 'estimacion';

    public $timestamps = false;

    protected $fillable = [
        'ejecucion_id', 'inscripcion_id',
        'asistencia', 'calificaciones', 'entregas', 'participacion', 'probabilidad',
    ];

    protected function casts(): array
    {
        return [
            'asistencia' => 'decimal:4',
            'calificaciones' => 'decimal:4',
            'entregas' => 'decimal:4',
            'participacion' => 'decimal:4',
            'probabilidad' => 'decimal:4',
        ];
    }

    public function ejecucion(): BelongsTo
    {
        return $this->belongsTo(EjecucionEstimacion::class, 'ejecucion_id');
    }

    public function inscripcion(): BelongsTo
    {
        return $this->belongsTo(Inscripcion::class);
    }
}