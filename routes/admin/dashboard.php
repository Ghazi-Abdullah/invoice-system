<?php

// routes/admin/dashboard.php

use App\Http\Controllers\Admin\DashboardController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->group(function () {
    //Route::middleware(['auth:sanctum', 'branch'])->group(function () {

    Route::get('/dashboard', [DashboardController::class, 'dashboard']);
    Route::get('/dashboard/stats', [DashboardController::class, 'stats']);
    Route::get('/dashboard/monthly-revenue', [DashboardController::class, 'monthlyRevenue']);
    Route::get('/dashboard/overdue-invoices', [DashboardController::class, 'overdueInvoices']);
    Route::get('/dashboard/recent-activity', [DashboardController::class, 'recentActivity']);
    Route::get('/dashboard/recent-invoices', [DashboardController::class, 'recentInvoices']);
    Route::get('/dashboard/recent-clients', [DashboardController::class, 'recentClients']);
    Route::post('/dashboard/report', [DashboardController::class, 'dashboardReport']);
});
