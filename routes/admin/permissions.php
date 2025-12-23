<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\PermissionController;

// Permissions Routes
Route::prefix('permissions')->group(function () {
    Route::get('/', [PermissionController::class, 'index']);
    Route::post('/', [PermissionController::class, 'store']);
    Route::get('/all', [PermissionController::class, 'getAll']);

    Route::prefix('{id}')->group(function () {
        Route::get('/', [PermissionController::class, 'show']);
        Route::put('/', [PermissionController::class, 'update']);
        Route::delete('/', [PermissionController::class, 'destroy']);
    });
});
