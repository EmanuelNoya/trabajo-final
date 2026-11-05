<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Estudiante extends Model
{
    protected $table = 'estudiante';

    public $timestamps = false;

    protected $fillable = ['legajo', 'apellido', 'nombre', 'documento'];

    public function inscripciones(): HasMany
    {
        return $this->hasMany(Inscripcion::class);
    }
}