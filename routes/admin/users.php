<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\PermissionController;

Route::group(['prefix' => 'users'], function () {
    Route::get('/', [UserController::class, 'index']);
    Route::post('/', [UserController::class, 'store']);
    Route::get('/clients/all', [UserController::class, 'clients']);
    Route::get('/staff/all', [UserController::class, 'staff']);
    Route::get('/groups', [UserController::class, 'adminGroups']);
    Route::post('/groups', [UserController::class, 'storeAdminGroup']);
    Route::get('/groups/{id}', [UserController::class, 'showAdminGroup']);
    Route::put('/groups/{id}', [UserController::class, 'updateAdminGroup']);
    Route::delete('/groups/{id}', [UserController::class, 'destroyAdminGroup']);
    Route::put('/profile/change-password', [UserController::class, 'changePassword']);
    Route::get('/profile/me', [UserController::class, 'profile']);
    Route::put('/profile/update', [UserController::class, 'updateProfile']);
    Route::get('/{id}', [UserController::class, 'show']);
    Route::put('/{id}', [UserController::class, 'update']);
    Route::delete('/{id}', [UserController::class, 'destroy']);
    Route::put('/{id}/status', [UserController::class, 'updateStatus']);
    
});
