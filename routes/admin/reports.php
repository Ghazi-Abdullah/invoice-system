<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\ReportController;

Route::group(['prefix' => 'reports'], function () {
    Route::get('/clients', [ReportController::class, 'clientReport']);
    Route::post('/clients/export', [ReportController::class, 'exportClientReport']);
    Route::get('/invoices', [ReportController::class, 'invoiceReport']);
    Route::post('/invoices/export', [ReportController::class, 'exportInvoiceReport']);
    Route::get('/payments', [ReportController::class, 'paymentReport']);
    Route::post('/payments/export', [ReportController::class, 'exportPaymentReport']);
    Route::get('/recent-activity', [ReportController::class, 'recentActivity']);
    Route::get('/tax', [ReportController::class, 'taxReport']);
});
