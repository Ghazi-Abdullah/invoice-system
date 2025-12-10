<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ClientController;
use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Api\PermissionController;
use App\Http\Controllers\Api\InvoiceReportController;

// Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Protected routes
Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);

    // Invoice routes
    Route::get('/invoices', [InvoiceController::class, 'index']);
    Route::post('/invoices', [InvoiceController::class, 'store']);
    Route::get('/invoices/{id}', [InvoiceController::class, 'show']);
    Route::put('/invoices/{id}', [InvoiceController::class, 'update']);
    Route::delete('/invoices/{id}', [InvoiceController::class, 'destroy']);
    Route::post('/invoices/{id}/status', [InvoiceController::class, 'updateStatus']);
    Route::get('/invoices/dashboard/stats', [InvoiceController::class, 'dashboardStats']);

    // Client routes
    Route::get('/clients', [ClientController::class, 'index']);
    Route::post('/clients', [ClientController::class, 'store']);
    Route::get('/clients/{client}', [ClientController::class, 'show']);
    Route::put('/clients/{client}', [ClientController::class, 'update']);
    Route::delete('/clients/{client}', [ClientController::class, 'destroy']);
    Route::get('/clients/list/simple', [ClientController::class, 'getSimpleList']);

    // Report routes
    Route::get('/reports/invoices', [InvoiceReportController::class, 'index']);
    Route::get('/reports/invoices/clients', [InvoiceReportController::class, 'clients']);
    Route::get('/reports/invoices/revenue', [InvoiceReportController::class, 'revenue']);
    Route::get('/reports/invoices/overdue', [InvoiceReportController::class, 'overdue']);

    // Permission Management Routes (for admin only)
    Route::middleware(['admin'])->group(function () {
        Route::get('/permissions/roles', [PermissionController::class, 'getRoles']);
        Route::get('/permissions/permissions', [PermissionController::class, 'getPermissions']);
        Route::get('/permissions/users', [PermissionController::class, 'getUsersWithRoles']);
        Route::post('/permissions/roles', [PermissionController::class, 'createRole']);
        Route::put('/permissions/roles/{id}', [PermissionController::class, 'updateRole']);
        Route::delete('/permissions/roles/{id}', [PermissionController::class, 'deleteRole']);
        Route::post('/permissions/roles/{roleId}/assign-permissions', [PermissionController::class, 'assignPermissionsToRole']);
        Route::post('/permissions/users/{userId}/assign-roles', [PermissionController::class, 'assignRolesToUser']);
        Route::get('/permissions/users/{userId}/roles', [PermissionController::class, 'getUserRoles']);
    });
});
