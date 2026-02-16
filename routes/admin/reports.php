<?php

use App\Http\Controllers\Admin\ReportController;
use Illuminate\Support\Facades\Route;

// إزالة prefix('admin') الإضافي لأن المجموعة الخارجية تضيفه بالفعل
Route::prefix('reports')->group(function () {

    Route::get('invoices', [ReportController::class, 'invoices']);
    Route::get('clients', [ReportController::class, 'clients']);
    Route::get('revenue', [ReportController::class, 'revenue']);
    Route::get('overdue', [ReportController::class, 'overdue']);

    Route::get('export/{type}', [ReportController::class, 'export']);
    Route::get('exported-files', [ReportController::class, 'exportedFiles']);
    Route::delete('exported-files/delete', [ReportController::class, 'deleteExportedFile']);

    Route::post('send-reminder/{invoice}', [ReportController::class, 'sendReminder']);
    Route::post('mark-paid/{invoice}', [ReportController::class, 'markAsPaid']);
});
