<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\PermissionController;

Route::group(['prefix' => 'permissions'], function () {
    Route::get('/', [PermissionController::class, 'index']);
    Route::get('/menus', [PermissionController::class, 'getMenus']);
    Route::get('/my-permissions', [PermissionController::class, 'getUserPermissions']);
    Route::get('/groups/{groupId}/permissions', [PermissionController::class, 'getGroupPermissions']);
    Route::put('/groups/{groupId}/permissions', [PermissionController::class, 'updateGroupPermissions']);
});
