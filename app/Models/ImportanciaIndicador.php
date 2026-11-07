<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportanciaIndicador extends Model
{
    protected $table = 'importancia_indicador';

    // Clave primaria compuesta (modelo_id, indicador): se escribe con insert() y se lee por la relación
    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = ['modelo_id', 'indicador', 'importancia'];

    protected function casts(): array
    {
        return [
            'importancia' => 'decimal:4',
        ];
    }

    public function modelo(): BelongsTo
    {
        return $this->belongsTo(Modelo::class);
    }
}