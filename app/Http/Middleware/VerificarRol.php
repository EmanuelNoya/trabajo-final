<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerificarRol
{
    //en rutas: ->middleware('rol:docente') o ->middleware('rol:docente,responsable')

    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (! in_array($request->user()?->rol, $roles, true)) {
            abort(403, 'No tenés permiso para acceder a esta sección.');
        }

        return $next($request);
    }
}