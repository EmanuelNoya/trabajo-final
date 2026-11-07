<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Modelo extends Model
{
    protected $table = 'modelo';

    public $timestamps = false;

    protected $fillable = [
        'version', 'algoritmo', 'corte', 'sensibilidad', 'medida_f1',
        'fecha_entrenamiento', 'ruta_archivo', 'hash_archivo', 'vigente',
    ];

    protected function casts(): array
    {
        return [
            'corte' => 'decimal:4',
            'sensibilidad' => 'decimal:4',
            'medida_f1' => 'decimal:4',
            'fecha_entrenamiento' => 'date',
            'vigente' => 'boolean',
        ];
    }

    public function importancias(): HasMany
    {
        return $this->hasMany(ImportanciaIndicador::class);
    }
}