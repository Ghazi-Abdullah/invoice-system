<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\PaymentController;

Route::group(['prefix' => 'payments'], function () {

    Route::post('/create-session/{invoice}', [PaymentController::class, 'createCheckoutSession'])
        ->name('payments.create-session');

    Route::get('/', [PaymentController::class, 'index'])
        ->name('payments.index');

    Route::get('/{id}', [PaymentController::class, 'show'])
        ->name('payments.show');

    // ✅ صلاحية مضافة — عملية مالية حساسة
    Route::post('/{id}/refund', [PaymentController::class, 'refund'])
        ->middleware('permission:refund_payments')
        ->name('payments.refund');
});
