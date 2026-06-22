<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\PaymentController;

/*
|--------------------------------------------------------------------------
| Stripe Webhook — خارج الـ Group تماماً
|--------------------------------------------------------------------------
| يجب أن يكون هنا قبل أي middleware أو prefix
| لأن Stripe يرسله من خارج التطبيق بدون token
*/
Route::post('/payments/webhook', [PaymentController::class, 'handleWebhook'])
    ->withoutMiddleware(['auth:sanctum'])
    ->name('payments.webhook');

/*
|--------------------------------------------------------------------------
| Payment Routes — محمية بالمصادقة
|--------------------------------------------------------------------------
*/
Route::group(['prefix' => 'payments'], function () {

    // إنشاء جلسة دفع لفاتورة معينة
    Route::post('/create-session/{invoice}', [PaymentController::class, 'createCheckoutSession'])
        ->name('payments.create-session');

    // قائمة المدفوعات
    Route::get('/', [PaymentController::class, 'index'])
        ->name('payments.index');

    // تفاصيل دفعة معينة — /{id} يجب أن يكون آخر route دائماً
    Route::get('/{id}', [PaymentController::class, 'show'])
        ->name('payments.show');

    // استرجاع مبلغ
    Route::post('/{id}/refund', [PaymentController::class, 'refund'])
        ->name('payments.refund');
});
