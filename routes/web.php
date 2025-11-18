<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ElementController;

// API Routes
Route::prefix('api')->group(function () {
    Route::apiResource('elements', ElementController::class)->except(['destroy']);
    Route::post('elements/{id}/archive', [ElementController::class, 'archive'])->name('elements.archive');
    Route::post('elements/{id}/restore', [ElementController::class, 'restore'])->name('elements.restore');
    Route::delete('elements/{id}/force', [ElementController::class, 'forceDelete'])->name('elements.force-delete');
});

// Serve Vue.js app on root and all other non-API routes
Route::get('/{any?}', function () {
    return view('app');
})->where('any', '^(?!api).*$');
