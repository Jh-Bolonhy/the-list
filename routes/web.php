<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ElementController;
use App\Http\Controllers\Api\AuthController;

// API Routes - Authentication
Route::prefix('api')->group(function () {
    Route::get('csrf-token', [AuthController::class, 'csrfToken'])->name('auth.csrf-token');
    Route::post('register', [AuthController::class, 'register'])->name('auth.register');
    Route::post('login', [AuthController::class, 'login'])->name('auth.login');
    Route::post('logout', [AuthController::class, 'logout'])->name('auth.logout')->middleware('auth');
    Route::get('user', [AuthController::class, 'user'])->name('auth.user');
    Route::put('user/headline', [AuthController::class, 'updateHeadline'])->name('auth.update-headline')->middleware('auth');
    Route::put('user/locale', [AuthController::class, 'updateLocale'])->name('auth.update-locale')->middleware('auth');
    
    // Protected routes - require authentication
    Route::middleware('auth')->group(function () {
        Route::apiResource('elements', ElementController::class)->except(['destroy']);
        Route::post('elements/{id}/archive', [ElementController::class, 'archive'])->name('elements.archive');
        Route::post('elements/{id}/restore', [ElementController::class, 'restore'])->name('elements.restore');
        Route::delete('elements/{id}/force', [ElementController::class, 'forceDelete'])->name('elements.force-delete');
    });
});

// Serve Vue.js app on root and all other non-API routes
Route::get('/{any?}', function () {
    return view('app');
})->where('any', '^(?!api).*$');
