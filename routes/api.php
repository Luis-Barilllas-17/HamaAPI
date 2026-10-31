<?php

use App\Http\Controllers\Api\ApiConnectionController;
use App\Http\Controllers\Api\HamaColorController;
use App\Http\Controllers\Api\HamaController;
use App\Http\Controllers\Api\InventoryColorController;
use Illuminate\Support\Facades\Route;

$registerHamaRoutes = static function (): void {
    Route::get('hamas', [HamaController::class, 'index'])
        ->middleware('scope:hamas.read')
        ->name('hamas.index');

    Route::post('hamas', [HamaController::class, 'store'])
        ->middleware('scope:hamas.write')
        ->name('hamas.store');

    Route::get('hamas/{hama}', [HamaController::class, 'show'])
        ->middleware('scope:hamas.read')
        ->name('hamas.show');

    Route::match(['put', 'patch'], 'hamas/{hama}', [HamaController::class, 'update'])
        ->middleware('scope:hamas.write')
        ->name('hamas.update');

    Route::delete('hamas/{hama}', [HamaController::class, 'destroy'])
        ->middleware('scope:hamas.delete')
        ->name('hamas.destroy');

    Route::get('colors', HamaColorController::class)
        ->middleware('scope:hamas.read')
        ->name('colors.index');
};

// Rutas estables para que otras aplicaciones consuman la API.
Route::prefix('v1')->name('v1.')->group($registerHamaRoutes);

Route::post('v1/connections/handshake', [ApiConnectionController::class, 'handshake'])
    ->middleware('client')
    ->name('v1.connections.handshake');

Route::prefix('v1')->name('v1.')->group(function (): void {
    Route::get('inventory-colors', [InventoryColorController::class, 'index'])
        ->middleware('scope:hamas.read')
        ->name('inventory-colors.index');
    Route::post('inventory-colors', [InventoryColorController::class, 'store'])
        ->middleware('scope:hamas.write')
        ->name('inventory-colors.store');
    Route::match(['put', 'patch'], 'inventory-colors/{inventoryColor}', [InventoryColorController::class, 'update'])
        ->middleware('scope:hamas.write')
        ->name('inventory-colors.update');
    Route::delete('inventory-colors/{inventoryColor}', [InventoryColorController::class, 'destroy'])
        ->middleware('scope:hamas.delete')
        ->name('inventory-colors.destroy');
});

// Compatibilidad con la colección y clientes creados antes del versionado.
Route::name('legacy.')->group($registerHamaRoutes);
