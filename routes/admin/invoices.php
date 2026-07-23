<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\InvoiceController;

Route::group(['prefix' => 'invoices'], function () {
    Route::get('/', [InvoiceController::class, 'index']);
    Route::post('/', [InvoiceController::class, 'store']);
    Route::get('/dashboard/stats', [InvoiceController::class, 'dashboardStats']);
    Route::get('/notification-counts', [InvoiceController::class, 'notificationCounts']); // ✅ جديد
    Route::get('/overdue/all', [InvoiceController::class, 'overdueInvoices']);
    Route::get('/recent/all', [InvoiceController::class, 'recentInvoices']);
    Route::get('/{id}', [InvoiceController::class, 'show']);
    Route::put('/{id}', [InvoiceController::class, 'update']);
    Route::delete('/{id}', [InvoiceController::class, 'destroy']);
    Route::get('/{id}/download-pdf', [InvoiceController::class, 'downloadPDF']);
    Route::post('/{id}/duplicate', [InvoiceController::class, 'duplicate']);
    Route::get('/{id}/generate-pdf', [InvoiceController::class, 'generatePDF']);
    Route::put('/{id}/mark-paid', [InvoiceController::class, 'markAsPaid']);
    Route::post('/{id}/send', [InvoiceController::class, 'send']);
    Route::get('/{id}/installment-plan', [InvoiceController::class, 'getInstallmentPlan']);
});
