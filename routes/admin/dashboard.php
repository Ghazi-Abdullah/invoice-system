<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\DashboardController;

Route::group(['prefix' => 'dashboard'], function () {
    Route::get('/', [DashboardController::class, 'dashboard']);
    Route::get('/stats', [DashboardController::class, 'stats']);
    Route::get('/monthly-revenue', [DashboardController::class, 'monthlyRevenue']);
    Route::get('/overdue-invoices', [DashboardController::class, 'overdueInvoices']);
    Route::get('/recent-activity', [DashboardController::class, 'recentActivity']);
    Route::get('/recent-invoices', [DashboardController::class, 'recentInvoices']);
    Route::get('/top-clients', [DashboardController::class, 'topClients']);
});
