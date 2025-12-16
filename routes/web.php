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
    Route::put('user/show-mode', [AuthController::class, 'updateShowMode'])->name('auth.update-show-mode')->middleware('auth');
    Route::put('user/locked-element', [AuthController::class, 'updateLockedElement'])->name('auth.update-locked-element')->middleware('auth');
    
    // Protected routes - require authentication
    Route::middleware('auth')->group(function () {
        // Custom routes must be defined BEFORE apiResource to avoid route conflicts
        Route::put('elements/reorder', [ElementController::class, 'reorder'])->name('elements.reorder');
        Route::put('elements/move', [ElementController::class, 'move'])->name('elements.move');
        Route::put('elements/{id}/toggle-collapse', [ElementController::class, 'toggleCollapse'])->name('elements.toggle-collapse');
        Route::post('elements/{id}/archive', [ElementController::class, 'archive'])->name('elements.archive');
        Route::post('elements/{id}/restore', [ElementController::class, 'restore'])->name('elements.restore');
        Route::delete('elements/{id}/force', [ElementController::class, 'forceDelete'])->name('elements.force-delete');
        Route::apiResource('elements', ElementController::class)->except(['destroy']);
    });
});

// Serve Vue.js app on root and all other non-API routes
Route::get('/{any?}', function () {
    return view('app');
})->where('any', '^(?!api).*$');
