<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Entrega extends Model
{
    protected $table = 'entrega';

    public $timestamps = false;

    protected $fillable = ['evaluacion_id', 'inscripcion_id', 'carga_id', 'fecha_entrega', 'calificacion'];

    protected function casts(): array
    {
        return [
            'fecha_entrega' => 'date',
            'calificacion' => 'decimal:2',
        ];
    }

    public function evaluacion(): BelongsTo
    {
        return $this->belongsTo(Evaluacion::class);
    }

    public function inscripcion(): BelongsTo
    {
        return $this->belongsTo(Inscripcion::class);
    }

    public function carga(): BelongsTo
    {
        return $this->belongsTo(CargaArchivo::class, 'carga_id');
    }

    //Estado deducido de las fechas
    public function estado(): string
    {
        if ($this->fecha_entrega === null) {
            return 'no_presentada';
        }

        return $this->fecha_entrega->gt($this->evaluacion->fecha_vencimiento) ? 'fuera_de_plazo' : 'en_plazo';
    }
}