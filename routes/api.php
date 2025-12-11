<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ClientController;
use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\AdminGroupController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\PermissionController;

// Public routes
Route::post('/login', [AuthController::class, 'login']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // Clients with permission middleware
    Route::middleware('permission:view_clients')->get('/clients', [ClientController::class, 'index']);
    Route::middleware('permission:view_clients')->get('/clients/{client}', [ClientController::class, 'show']);
    Route::middleware('permission:create_client')->post('/clients', [ClientController::class, 'store']);
    Route::middleware('permission:edit_client')->put('/clients/{client}', [ClientController::class, 'update']);
    Route::middleware('permission:delete_client')->delete('/clients/{client}', [ClientController::class, 'destroy']);

    // Invoices with permission middleware
    Route::middleware('permission:view_invoices')->get('/invoices', [InvoiceController::class, 'index']);
    Route::middleware('permission:view_invoices')->get('/invoices/{invoice}', [InvoiceController::class, 'show']);
    Route::middleware('permission:create_invoice')->post('/invoices', [InvoiceController::class, 'store']);
    Route::middleware('permission:edit_invoice')->put('/invoices/{invoice}', [InvoiceController::class, 'update']);
    Route::middleware('permission:delete_invoice')->delete('/invoices/{invoice}', [InvoiceController::class, 'destroy']);

    // Reports with permission middleware
    Route::middleware('permission:view_sales_report')->get('/reports/sales', [ReportController::class, 'salesReport']);
    Route::middleware('permission:export_reports')->post('/reports/export', [ReportController::class, 'export']);

    // Administration routes - only for users with administration permission
    Route::prefix('admin')->middleware('permission:administration')->group(function () {
        Route::apiResource('groups', AdminGroupController::class);
        Route::post('groups/{adminGroup}/permissions', [AdminGroupController::class, 'updatePermissions']);
        Route::get('groups/{adminGroup}/available-permissions', [AdminGroupController::class, 'getAvailablePermissions']);

        Route::apiResource('users', UserController::class);
        Route::get('users/groups/list', [UserController::class, 'getGroups']);

        Route::get('permissions', [PermissionController::class, 'index']);
        Route::get('permissions/menus', [PermissionController::class, 'getMenusWithPermissions']);
    });

    // Get user permissions
    Route::get('/user-permissions', [PermissionController::class, 'getUserPermissions']);
});
