<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ClientController;
use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\AdminGroupController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\PermissionController;
use App\Http\Controllers\Api\DashboardController;

// Public routes
Route::post('/login', [AuthController::class, 'login']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // Test endpoints
    Route::get('/test/clients', function () {
        $clients = \App\Models\Client::all();

        return response()->json([
            'status' => true,
            'message' => 'Test endpoint',
            'total_clients' => $clients->count(),
            'clients' => $clients->take(5)
        ]);
    });

    // Dashboard
    Route::get('/dashboard/stats', [DashboardController::class, 'getStats']);
    Route::get('/dashboard/recent-data', [DashboardController::class, 'getRecentData']);

    // Clients with permission checking
    Route::get('/clients', [ClientController::class, 'index']);
    Route::get('/clients/{id}', [ClientController::class, 'show']);
    Route::post('/clients', [ClientController::class, 'store']);
    Route::put('/clients/{id}', [ClientController::class, 'update']);
    Route::delete('/clients/{id}', [ClientController::class, 'destroy']);

    // Invoices with permission checking
    Route::get('/invoices', [InvoiceController::class, 'index']);
    Route::get('/invoices/{id}', [InvoiceController::class, 'show']);
    Route::post('/invoices', [InvoiceController::class, 'store']);
    Route::put('/invoices/{id}', [InvoiceController::class, 'update']);
    Route::put('/invoices/{id}/status', [InvoiceController::class, 'updateStatus']);
    Route::delete('/invoices/{id}', [InvoiceController::class, 'destroy']);

    // Reports with permission checking
    Route::get('/reports/sales', [ReportController::class, 'salesReport']);
    Route::post('/reports/export', [ReportController::class, 'export']);

    // Administration routes - only for users with administration permission
    // Administration routes - only for users with administration permission
    Route::prefix('admin')->group(function () {
        // Admin groups
        Route::get('/groups', [AdminGroupController::class, 'index']);
        Route::get('/groups/{id}', [AdminGroupController::class, 'show']);
        Route::post('/groups', [AdminGroupController::class, 'store']);
        Route::put('/groups/{id}', [AdminGroupController::class, 'update']);
        Route::delete('/groups/{id}', [AdminGroupController::class, 'destroy']);
        Route::post('/groups/{id}/permissions', [AdminGroupController::class, 'updatePermissions']);
        Route::get('/groups/{id}/available-permissions', [AdminGroupController::class, 'getAvailablePermissions']);

        // Users management
        Route::get('/users', [UserController::class, 'index']);
        Route::get('/users/{id}', [UserController::class, 'show']);
        Route::post('/users', [UserController::class, 'store']);
        Route::put('/users/{id}', [UserController::class, 'update']);
        Route::delete('/users/{id}', [UserController::class, 'destroy']);
        Route::get('/users/groups/list', [UserController::class, 'getGroups']);

        // Permissions (منفصلة)
        Route::get('/permissions', [PermissionController::class, 'index']);
        Route::get('/permissions/menus', [PermissionController::class, 'getMenusWithPermissions']);
        Route::post('/permissions', [PermissionController::class, 'store']);
        Route::get('/permissions/{id}', [PermissionController::class, 'show']);
        Route::put('/permissions/{id}', [PermissionController::class, 'update']);
        Route::delete('/permissions/{id}', [PermissionController::class, 'destroy']);
    });

    // Get user permissions
    Route::get('/user-permissions', function () {
        $user = auth()->user();

        if (!$user->group) {
            return response()->json([
                'status' => true,
                'message' => 'صلاحيات المستخدم',
                'data' => [
                    'permissions' => [],
                    'is_admin' => false
                ]
            ]);
        }

        return response()->json([
            'status' => true,
            'message' => 'صلاحيات المستخدم',
            'data' => [
                'permissions' => $user->group->permissions,
                'is_admin' => $user->admin_group_id == 1
            ]
        ]);
    });
});
