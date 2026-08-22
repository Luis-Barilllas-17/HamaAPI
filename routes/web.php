<?php

use Illuminate\Support\Facades\Route;

// Interfaz visual opcional. Todo el CRUD se expone exclusivamente en routes/api.php.
Route::view('/', 'hamas.index');
Route::view('/hamas', 'hamas.index')->name('hamas.dashboard');
Route::view('/respuesta', 'hamas.api-viewer')->name('hamas.api-viewer');
