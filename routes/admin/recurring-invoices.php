<?php

use App\Http\Controllers\Admin\RecurringInvoiceController;
use Illuminate\Support\Facades\Route;

Route::prefix('recurring-invoices')->group(function () {
    Route::get('/', [RecurringInvoiceController::class, 'index']);
    Route::get('{id}', [RecurringInvoiceController::class, 'show']);
    Route::post('/', [RecurringInvoiceController::class, 'store']);
    Route::put('{id}', [RecurringInvoiceController::class, 'update']);
    Route::delete('{id}', [RecurringInvoiceController::class, 'destroy']);
    Route::post('{id}/generate-now', [RecurringInvoiceController::class, 'generateNow']);
    Route::post('{id}/cancel', [RecurringInvoiceController::class, 'cancel']);
});