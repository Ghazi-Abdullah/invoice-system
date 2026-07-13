<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\PaymentController;

/*
|--------------------------------------------------------------------------
| ملاحظة: راوت الـ webhook (admin/payments/webhook) مسجّل فعلياً بملف
| routes/api.php مباشرة (خارج أي middleware)، فلا داعي لتكراره هنا —
| كان هذا التعريف ميت تماماً (Laravel يطابق أول تسجيل لنفس الرابط).
*/

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
