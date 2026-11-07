<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RecuperacionContrasenaController;
use App\Http\Controllers\InicioController;
use App\Http\Controllers\Administrador\UsuarioController;
use App\Http\Controllers\Analista\CursoController;
use App\Http\Controllers\Analista\IncorporacionController;
use App\Http\Controllers\Analista\IndicadoresController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store']);

    // Recuperación de contraseña
    Route::get('/recuperar-contrasena', [RecuperacionContrasenaController::class, 'solicitar'])
        ->name('password.request');
    Route::post('/recuperar-contrasena', [RecuperacionContrasenaController::class, 'enviarEnlace'])
        ->middleware('throttle:5,1')->name('password.email');
    Route::get('/restablecer-contrasena/{token}', [RecuperacionContrasenaController::class, 'formulario'])
        ->name('password.reset');
    Route::post('/restablecer-contrasena', [RecuperacionContrasenaController::class, 'restablecer'])
        ->name('password.store');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');
    Route::get('/', InicioController::class)->name('inicio');

    Route::view('/docente', 'inicio', ['titulo' => 'Mis cursos'])
        ->middleware('rol:docente')->name('docente.inicio');
        
    Route::view('/responsable', 'inicio', ['titulo' => 'Cursos de la carrera'])
        ->middleware('rol:responsable')->name('responsable.inicio');

    Route::middleware('rol:analista')->prefix('analista')->group(function () {
        $tipos = implode('|', array_keys(IncorporacionController::IMPORTADORES)); // actividad|calificaciones

        Route::resource('cursos', CursoController::class)->only(['index', 'create', 'store', 'show']);
        Route::post('cursos/{curso}/nomina', [CursoController::class, 'incorporarNomina'])
            ->name('cursos.nomina');
        Route::post('cursos/{curso}/incorporar/{tipo}', [IncorporacionController::class, 'incorporar'])
            ->where('tipo', $tipos)->name('cursos.incorporar');
        Route::get('cursos/{curso}/incorporar/{tipo}/confirmar', [IncorporacionController::class, 'confirmar'])
            ->where('tipo', $tipos)->name('cursos.incorporar.confirmar');
        Route::post('cursos/{curso}/incorporar/{tipo}/confirmar', [IncorporacionController::class, 'reemplazar'])
            ->where('tipo', $tipos)->name('cursos.incorporar.reemplazar');
        Route::delete('cursos/{curso}/incorporar/{tipo}/confirmar', [IncorporacionController::class, 'cancelar'])
            ->where('tipo', $tipos)->name('cursos.incorporar.cancelar');
        Route::get('cursos/{curso}/indicadores', IndicadoresController::class)
            ->name('cursos.indicadores');
    });

    Route::middleware('rol:administrador')->prefix('administrador')->group(function () {
        Route::resource('usuarios', UsuarioController::class)->except(['show', 'destroy']);
        Route::patch('usuarios/{usuario}/desactivar', [UsuarioController::class, 'desactivar'])
            ->name('usuarios.desactivar');
        Route::patch('usuarios/{usuario}/activar', [UsuarioController::class, 'activar'])
            ->name('usuarios.activar');
    });
});