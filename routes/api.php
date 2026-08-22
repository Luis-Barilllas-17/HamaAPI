<?php

use App\Http\Controllers\Api\HamaController;
use Illuminate\Support\Facades\Route;

Route::apiResource('hamas', HamaController::class);
