<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AdminGroupController;

// Admin Groups Routes
Route::prefix('groups')->group(function () {
    Route::get('/', [AdminGroupController::class, 'index']);
    Route::post('/', [AdminGroupController::class, 'store']);

    Route::get('/available-permissions', [AdminGroupController::class, 'availablePermissions']);
    Route::get('/with-permissions', [AdminGroupController::class, 'groupsWithPermissions']);
    Route::get('/simple-list', [AdminGroupController::class, 'simpleList']);

    Route::prefix('{id}')->group(function () {
        Route::get('/', [AdminGroupController::class, 'show']);
        Route::put('/', [AdminGroupController::class, 'update']);
        Route::delete('/', [AdminGroupController::class, 'destroy']);

        Route::get('/permissions', [AdminGroupController::class, 'permissions']);
        Route::put('/permissions', [AdminGroupController::class, 'updatePermissions']);
    });
});
