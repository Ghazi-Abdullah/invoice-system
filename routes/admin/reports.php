<?php

use App\Http\Controllers\Admin\ReportController;
use Illuminate\Support\Facades\Route;

Route::prefix('reports')->group(function () {
    Route::get('invoices', [ReportController::class, 'getInvoiceReport']);
    Route::get('clients', [ReportController::class, 'getClientReport']);
    Route::get('revenue', [ReportController::class, 'getRevenueReport']);
    Route::get('overdue', [ReportController::class, 'getOverdueReport']);
    Route::get('dashboard-stats', [ReportController::class, 'getDashboardStats']);
    Route::get('export/{type}', [ReportController::class, 'exportReport']);
    Route::post('send-reminder/{invoice}', [ReportController::class, 'sendReminder']);
    Route::post('mark-paid/{invoice}', [ReportController::class, 'markAsPaid']);
});
