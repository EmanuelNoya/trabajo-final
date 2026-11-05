<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Carrera extends Model
{
    protected $table = 'carrera';

    public $timestamps = false;

    protected $fillable = ['nombre', 'responsable_id'];

    public function responsable(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'responsable_id');
    }

    public function cursos(): HasMany
    {
        return $this->hasMany(Curso::class);
    }
}