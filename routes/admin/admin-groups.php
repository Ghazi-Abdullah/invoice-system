<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AdminGroupController;

Route::prefix('admin-groups')->group(function () {
    Route::get('/', [AdminGroupController::class, 'index']);
    Route::post('/', [AdminGroupController::class, 'store']);
    Route::get('/{id}', [AdminGroupController::class, 'show']);
    Route::put('/{id}', [AdminGroupController::class, 'update']);
    Route::delete('/{id}', [AdminGroupController::class, 'destroy']);

    Route::get('/{id}/permissions', [AdminGroupController::class, 'permissions']);
    Route::put('/{id}/permissions', [AdminGroupController::class, 'updatePermissions']);
    Route::get('/available-permissions', [AdminGroupController::class, 'availablePermissions']);
    Route::get('/with-permissions', [AdminGroupController::class, 'groupsWithPermissions']);
});
