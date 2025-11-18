<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ElementController;

// API Routes
Route::prefix('api')->group(function () {
    Route::apiResource('elements', ElementController::class);
});

// Serve Vue.js app on root and all other non-API routes
Route::get('/{any?}', function () {
    return view('app');
})->where('any', '^(?!api).*$');
