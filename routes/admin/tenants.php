<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\TenantController;

Route::group(['prefix' => 'tenants'], function () {
    Route::get('/', [TenantController::class, 'index']);
    Route::post('/', [TenantController::class, 'store']);
    Route::get('/{id}', [TenantController::class, 'show']);
    Route::put('/{id}', [TenantController::class, 'update']);
    Route::delete('/{id}', [TenantController::class, 'destroy']);
});