<?php

use App\Http\Controllers\Api\ApiConnectionController;
use App\Http\Controllers\Api\HamaController;
use Illuminate\Support\Facades\Route;

$registerHamaRoutes = static function (): void {
    Route::get('hamas', [HamaController::class, 'index'])
        ->middleware('client:hamas.read')
        ->name('hamas.index');

    Route::post('hamas', [HamaController::class, 'store'])
        ->middleware('client:hamas.write')
        ->name('hamas.store');

    Route::get('hamas/{hama}', [HamaController::class, 'show'])
        ->middleware('client:hamas.read')
        ->name('hamas.show');

    Route::match(['put', 'patch'], 'hamas/{hama}', [HamaController::class, 'update'])
        ->middleware('client:hamas.write')
        ->name('hamas.update');

    Route::delete('hamas/{hama}', [HamaController::class, 'destroy'])
        ->middleware('client:hamas.delete')
        ->name('hamas.destroy');
};

// Rutas estables para que otras aplicaciones consuman la API.
Route::prefix('v1')->name('v1.')->group($registerHamaRoutes);

Route::post('v1/connections/handshake', [ApiConnectionController::class, 'handshake'])
    ->middleware('client')
    ->name('v1.connections.handshake');

// Compatibilidad con la colección y clientes creados antes del versionado.
Route::name('legacy.')->group($registerHamaRoutes);
