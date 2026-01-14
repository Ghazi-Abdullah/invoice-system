<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\PaymentController;

Route::group(['prefix' => 'payments'], function () {
    // إنشاء جلسة دفع
    Route::post('/create-session/{invoice}', [PaymentController::class, 'createCheckoutSession']);

    // الحصول على المدفوعات
    Route::get('/', [PaymentController::class, 'index']);
    Route::get('/{id}', [PaymentController::class, 'show']);

    // استرجاع المبلغ
    Route::post('/{id}/refund', [PaymentController::class, 'refund']);

    // Stripe Webhook (بدون مصادقة)
    Route::post('/webhook', [PaymentController::class, 'handleWebhook'])->withoutMiddleware(['auth:sanctum']);
});
