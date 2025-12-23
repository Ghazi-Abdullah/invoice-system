<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/
// Protected routes
Route::middleware(['auth:sanctum', 'check.sanctum'])->group(function () {
    Route::post('logout', [\App\Http\Controllers\Admin\AuthController::class, 'logout']);
    Route::get('me', [\App\Http\Controllers\Admin\AuthController::class, 'me']);
    Route::post('refresh', [\App\Http\Controllers\Admin\AuthController::class, 'refresh']);
});

// Auth Routes (Public)
Route::prefix('admin')->group(function () {
    Route::post('login', [\App\Http\Controllers\Admin\AuthController::class, 'login']);
    Route::post('forgot-password', [\App\Http\Controllers\Admin\AuthController::class, 'forgotPassword']);
    Route::post('reset-password', [\App\Http\Controllers\Admin\AuthController::class, 'resetPassword']);

    // Protected routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', [\App\Http\Controllers\Admin\AuthController::class, 'logout']);
        Route::get('me', [\App\Http\Controllers\Admin\AuthController::class, 'me']);
        Route::post('refresh', [\App\Http\Controllers\Admin\AuthController::class, 'refresh']);
    });
});

// Protected Admin Routes (يجب حذف الـ routes المكررة هنا وتضمين الملفات فقط)
Route::prefix('admin')->middleware(['auth:sanctum'])->group(function () {
    // Load all admin route files
    require __DIR__ . '/admin/clients.php';
    require __DIR__ . '/admin/invoices.php';
    require __DIR__ . '/admin/users.php';
    require __DIR__ . '/admin/dashboard.php';
    require __DIR__ . '/admin/reports.php';
    require __DIR__ . '/admin/permissions.php';
    require __DIR__ . '/admin/admin-groups.php'; // أضفت هذا السطر
});
