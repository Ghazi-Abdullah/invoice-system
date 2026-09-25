<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\FloorController;

Route::group(['prefix' => 'floors'], function () {
    Route::get('/', [FloorController::class, 'index']);
    Route::post('/', [FloorController::class, 'store']);
    Route::get('/{id}', [FloorController::class, 'show']);
    Route::put('/{id}', [FloorController::class, 'update']);
    Route::delete('/{id}', [FloorController::class, 'destroy']);
});