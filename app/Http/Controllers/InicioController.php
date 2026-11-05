<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class InicioController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        return redirect()->route(match ($request->user()->rol) {
            'docente' => 'docente.inicio',
            'responsable' => 'responsable.inicio',
            'analista' => 'analista.inicio',
            'administrador' => 'usuarios.index',
        });
    }
}