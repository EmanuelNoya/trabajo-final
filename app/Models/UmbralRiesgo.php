<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UmbralRiesgo extends Model
{
    public const NIVELES = [
        'alto' => 'Riesgo alto',
        'medio' => 'Riesgo medio',
        'bajo' => 'Riesgo bajo',
    ];

    /** Valores sugeridos por la validación del modelo */
    public const MEDIO_POR_DEFECTO = 0.40;
    public const ALTO_POR_DEFECTO = 0.60;

    protected $table = 'umbral_riesgo';

    public $timestamps = false;

    protected $fillable = ['carrera_id', 'usuario_id', 'umbral_medio', 'umbral_alto', 'fecha_desde'];

    protected function casts(): array
    {
        return [
            'umbral_medio' => 'decimal:4',
            'umbral_alto' => 'decimal:4',
            'fecha_desde' => 'date',
        ];
    }

    public function carrera(): BelongsTo
    {
        return $this->belongsTo(Carrera::class);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class);
    }

    /**
     * Umbrales que rigen hoy para la carrera: el último con fecha_desde hasta hoy.
     * Si la carrera no tiene ninguno, devuelve los valores por defecto (sin guardarlos).
     */
    public static function vigenteDe(int $carreraId): self
    {
        return static::where('carrera_id', $carreraId)
            ->where('fecha_desde', '<=', today())
            ->orderByDesc('fecha_desde')
            ->orderByDesc('id')
            ->first()
            ?? new static([
                'umbral_medio' => self::MEDIO_POR_DEFECTO,
                'umbral_alto' => self::ALTO_POR_DEFECTO,
            ]);
    }

    /**
     * Nivel de riesgo de una probabilidad. No se guarda: se deriva cada vez.
     */
    public function nivel(float $probabilidad): string
    {
        return match (true) {
            $probabilidad >= (float) $this->umbral_alto => 'alto',
            $probabilidad >= (float) $this->umbral_medio => 'medio',
            default => 'bajo',
        };
    }
}