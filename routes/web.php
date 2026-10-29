<?php

use App\Http\Controllers\ConnectionTokenController;
use App\Http\Controllers\ExternalConnectionController;
use Illuminate\Support\Facades\Route;

// Interfaz visual opcional. Todo el CRUD se expone exclusivamente en routes/api.php.
Route::view('/', 'hamas.index');
Route::view('/hamas', 'hamas.index')->name('hamas.dashboard');
Route::view('/conexiones', 'hamas.api-viewer')->name('hamas.api-connections');
Route::post('/conexiones/token', [ConnectionTokenController::class, 'store'])
    ->middleware('throttle:10,1')
    ->name('hamas.api-connections.token');
Route::post('/conexiones/probar-externa', ExternalConnectionController::class)
    ->middleware('throttle:20,1')
    ->name('hamas.api-connections.external');
Route::redirect('/respuesta', '/conexiones');
