<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ClientController;
use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Api\InvoiceReportController;

Route::get('/test', function () {
    return response()->json([
        'message' => 'API is working!',
        'timestamp' => now()->toDateTimeString(),
        'version' => '1.0.0'
    ]);
});

// Authentication routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // Clients Routes
    Route::get('/clients', [ClientController::class, 'index']);
    Route::post('/clients', [ClientController::class, 'store']);
    Route::get('/clients/{client}', [ClientController::class, 'show']);
    Route::put('/clients/{client}', [ClientController::class, 'update']);
    Route::delete('/clients/{client}', [ClientController::class, 'destroy']);

    // Invoices
    Route::get('/invoices', [InvoiceController::class, 'index']);
    Route::post('/invoices', [InvoiceController::class, 'store']);
    Route::get('/invoices/{invoice}', [InvoiceController::class, 'show']);
    Route::put('/invoices/{invoice}', [InvoiceController::class, 'update']);
    Route::delete('/invoices/{invoice}', [InvoiceController::class, 'destroy']);
    Route::patch('/invoices/{invoice}/status', [InvoiceController::class, 'updateStatus']);

    // رابط جديد لربط الفواتير الافتراضية
    Route::post('/invoices/link-defaults', [InvoiceController::class, 'linkDefaultInvoices']);

    // ===== تقارير الفواتير =====
    Route::prefix('reports')->group(function () {
        // تقرير الفواتير الرئيسي (سيعمل مع مكون Vue.js الخاص بك)
        Route::get('/invoices', [InvoiceReportController::class, 'index']);

        // تقارير متخصصة
        Route::get('/invoices/clients', [InvoiceReportController::class, 'clientReport']);
        Route::get('/invoices/overdue', [InvoiceReportController::class, 'overdueReport']);
        Route::get('/invoices/revenue', [InvoiceReportController::class, 'revenueReport']);
    });
});
