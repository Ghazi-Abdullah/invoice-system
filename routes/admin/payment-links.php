<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\PaymentLinkController;

/*
|--------------------------------------------------------------------------
| Payment Links Routes (داخل /api/admin/)
|--------------------------------------------------------------------------
| لا حاجة لـ middleware هنا لأن api.php تُطبق auth:sanctum بالفعل
*/

Route::get("/invoices/{invoiceId}/payment-links", [PaymentLinkController::class, "index"]);
Route::post("/invoices/{invoiceId}/payment-links", [PaymentLinkController::class, "createForInvoice"]);
Route::post("/invoices/{invoiceId}/installments/{installmentNumber}/payment-links", [PaymentLinkController::class, "createForInstallment"]);
Route::post("/payment-links/{linkId}/send", [PaymentLinkController::class, "sendLink"]);
