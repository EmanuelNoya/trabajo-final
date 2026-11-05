<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\InicioController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store']);
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');
    Route::get('/', InicioController::class)->name('inicio');

    Route::view('/docente', 'inicio', ['titulo' => 'Mis cursos'])
        ->middleware('rol:docente')->name('docente.inicio');
    Route::view('/responsable', 'inicio', ['titulo' => 'Cursos de la carrera'])
        ->middleware('rol:responsable')->name('responsable.inicio');
    Route::view('/analista', 'inicio', ['titulo' => 'Incorporación de datos'])
        ->middleware('rol:analista')->name('analista.inicio');
    Route::view('/administrador', 'inicio', ['titulo' => 'Gestión de usuarios'])
        ->middleware('rol:administrador')->name('administrador.inicio');
});