<?php

use App\Http\Controllers\LandingController;
use Illuminate\Support\Facades\Route;

// Las rutas /api/* del panel viven en routes/web.php dentro del grupo 'auth', porque
// el frontend las consume con la sesion del usuario. Aqui no deben declararse
// rutas publicas que expongan datos de pacientes.

// Contenido publico de la landing (/inicio): sin sesion ni cookies para poder cachearse.
Route::middleware('throttle:120,1')->prefix('publico')->group(function () {
    Route::get('/promociones', [LandingController::class, 'promociones']);
    Route::get('/imagenes/{uuid}', [LandingController::class, 'imagen'])
        ->whereUuid('uuid')
        ->name('landing.imagen');
});
