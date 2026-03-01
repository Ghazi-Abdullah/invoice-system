<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\ActivityLogController;



/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
*/

// Auth Routes (Public)
Route::prefix('admin')->group(function () {
    Route::post('login', [AuthController::class, 'login']);
    Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('reset-password', [AuthController::class, 'resetPassword']);
});

// Protected Admin Routes
Route::prefix('admin')->middleware(['auth:sanctum'])->group(function () {
    // Load all admin route files
    require __DIR__ . '/admin/clients.php';
    require __DIR__ . '/admin/invoices.php';
    require __DIR__ . '/admin/users.php';
    require __DIR__ . '/admin/dashboard.php';
    require __DIR__ . '/admin/reports.php';
    require __DIR__ . '/admin/permissions.php';
    require __DIR__ . '/admin/admin-groups.php';
    require __DIR__ . '/admin/payments.php';

    // Auth routes inside protected group
    Route::post('logout', [AuthController::class, 'logout']);
    Route::get('me', [AuthController::class, 'me']);
    Route::post('refresh', [AuthController::class, 'refresh']);
    Route::get('/me', [UserController::class, 'profile']);
    Route::put('/profile', [UserController::class, 'updateProfile']);
    Route::post('/change-password', [UserController::class, 'changePassword']);
    Route::get('/activity-logs', [ActivityLogController::class, 'index']);
});
