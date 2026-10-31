<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ConnectionTokenController;
use App\Http\Controllers\MeshiverseController;
use App\Http\Controllers\SessionApiTokenController;
use Illuminate\Support\Facades\Route;

// Interfaz visual opcional. Todo el CRUD se expone exclusivamente en routes/api.php.
Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle:6,1')
        ->name('login.store');
    Route::get('/registro', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/registro', [AuthController::class, 'register'])
        ->middleware('throttle:4,1')
        ->name('register.store');
});

Route::middleware('auth')->group(function (): void {
    Route::redirect('/', '/proyectos');
    Route::view('/hamas', 'hamas.projects')->name('hamas.dashboard');
    Route::view('/proyectos', 'hamas.projects')->name('hamas.projects');
    Route::view('/crear-hama', 'hamas.create')->name('hamas.create');
    Route::view('/inventario', 'hamas.inventory')->name('hamas.inventory');
    Route::get('/meshiverse', [MeshiverseController::class, 'index'])->name('meshiverse.index');
    Route::get('/meshiverse/status', [MeshiverseController::class, 'status'])
        ->middleware('throttle:30,1')
        ->name('meshiverse.status');
    Route::view('/perfil', 'settings.profile')->name('profile');
    Route::view('/configuracion', 'settings.index')->name('settings.index');
    Route::post('/session/api-token', [SessionApiTokenController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('session.api-token');
    Route::middleware('admin')->group(function (): void {
        Route::view('/configuracion/api', 'hamas.api-viewer')->name('hamas.api-connections');
        Route::redirect('/conexiones', '/configuracion/api');
        Route::post('/conexiones/token', [ConnectionTokenController::class, 'store'])
            ->middleware('throttle:10,1')
            ->name('hamas.api-connections.token');
        Route::redirect('/respuesta', '/configuracion/api');
    });
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});
