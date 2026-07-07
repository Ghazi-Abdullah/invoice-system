<?php

use App\Http\Controllers\Admin\InstallmentInterestTierController;
use App\Http\Controllers\Admin\InstallmentPlanController;
use Illuminate\Support\Facades\Route;

// جدول نسب الفائدة الافتراضي (إعدادات)
Route::group(['prefix' => 'installment-interest-tiers'], function () {
    Route::get('/', [InstallmentInterestTierController::class, 'index']);
    Route::post('/', [InstallmentInterestTierController::class, 'store']);
    Route::delete('/{installmentInterestTier}', [InstallmentInterestTierController::class, 'destroy']);
});

// اقتراح نسبة الفائدة لعدد أقساط معيّن (لملء الحقل بالواجهة تلقائياً)
Route::get('installment-interest-tiers/suggest/{numberOfInstallments}', [InstallmentPlanController::class, 'suggestRate']);

// خطط الأقساط مرتبطة بفاتورة معيّنة
Route::group(['prefix' => 'invoices/{invoice}/installment-plan'], function () {
    Route::get('/', [InstallmentPlanController::class, 'show']);
    Route::post('/', [InstallmentPlanController::class, 'store']);
});

Route::delete('installment-plans/{installmentPlan}', [InstallmentPlanController::class, 'cancel']);
Route::put('installments/{installmentId}/pay', [InstallmentPlanController::class, 'payInstallment']);
