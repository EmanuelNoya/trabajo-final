<?php

namespace App\Policies;

use App\Models\Carrera;
use App\Models\Usuario;

class CarreraPolicy
{
    /**
     * HU-11: solo el responsable académico de la carrera configura sus umbrales (criterio 4).
     */
    public function configurarUmbrales(Usuario $usuario, Carrera $carrera): bool
    {
        return $usuario->rol === 'responsable' && $carrera->responsable()->is($usuario);
    }
}