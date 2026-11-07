<?php

namespace App\Policies;

use App\Models\Curso;
use App\Models\Usuario;

class CursoPolicy
{
    /**
     * HU-02, criterio 3, y tabla de permisos: el docente consulta solo sus cursos;
     * el responsable académico, los de su carrera.
     */
    public function verTablero(Usuario $usuario, Curso $curso): bool
    {
        return match ($usuario->rol) {
            'docente' => $curso->docente()->is($usuario),
            'responsable' => $curso->carrera->responsable()->is($usuario),
            default => false,
        };
    }
}