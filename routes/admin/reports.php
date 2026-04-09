<?php

use App\Http\Controllers\Admin\ReportController;
use Illuminate\Support\Facades\Route;

Route::prefix('reports')->group(function () {

    // ── تقارير عرض: Rate Limit عادي (مشمول من الـ group الخارجي) ─────
    Route::get('invoices', [ReportController::class, 'invoices']);
    Route::get('clients',  [ReportController::class, 'clients']);
    Route::get('revenue',  [ReportController::class, 'revenue']);
    Route::get('overdue',  [ReportController::class, 'overdue']);

    // ── Exports: ✅ Rate Limit مخصص (10 ملفات/5 دقائق) ───────────────
    // لأن توليد Excel يضغط على CPU والذاكرة
    Route::get('export/{type}', [ReportController::class, 'export'])
        ->middleware('throttle:exports')
        ->where('type', 'invoices|clients|revenue|overdue'); // ✅ تحقق من النوع في الـ Route

    // ── ملفات مُصدَّرة ──────────────────────────────────────────────
    Route::get('exported-files',           [ReportController::class, 'exportedFiles']);
    Route::delete('exported-files/delete', [ReportController::class, 'deleteExportedFile']);

    // ── إجراءات على الفواتير ─────────────────────────────────────────
    Route::post('send-reminder/{invoice}', [ReportController::class, 'sendReminder']);
    Route::post('mark-paid/{invoice}',     [ReportController::class, 'markAsPaid']);
});
