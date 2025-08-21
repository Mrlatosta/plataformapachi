<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\EstudioController;

Route::get('/estudios', [EstudioController::class, 'index']);
Route::post('/estudios', [EstudioController::class, 'store']);
Route::get('/estudios/{id}', [EstudioController::class, 'show']);
Route::put('/estudios/{id}', [EstudioController::class, 'update']);
