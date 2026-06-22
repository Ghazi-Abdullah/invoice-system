<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/pay/{id}', function ($id) {
    $invoice = App\Models\Invoice::findOrFail($id);
    $result = app(App\Repository\Admin\Payment\PaymentRepository::class)->createCheckoutSession($invoice);
    return $result['status'] ? redirect($result['data']['url']) : $result['message'];
});


Route::get('/test-payment/{invoiceId}', function ($invoiceId) {
    $invoice = App\Models\Invoice::findOrFail($invoiceId);
    $result = app(App\Repository\Admin\Payment\PaymentRepository::class)->createCheckoutSession($invoice);
    return response()->json($result);
});


