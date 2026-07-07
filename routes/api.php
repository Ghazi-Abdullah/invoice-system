<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\ActivityLogController;

use App\Http\Controllers\Admin\PaymentController;

// ✅ Webhook Stripe - لا يحتاج middleware
Route::post('/stripe/webhook', [PaymentController::class, 'handleWebhook'])->name('stripe.webhook');

/*
|--------------------------------------------------------------------------
| API Routes - Invoice System
|--------------------------------------------------------------------------
*/

// ============================================================
// Public Routes (بدون مصادقة)
// ============================================================
Route::prefix('admin')->group(function () {

    // ✅ Login: 5 محاولات فقط/دقيقة لكل IP (حماية Brute Force)
    Route::post('login', [AuthController::class, 'login'])
        ->middleware('throttle:login');

    // ✅ Forgot Password: 3 طلبات/15 دقيقة
    Route::post('forgot-password', [AuthController::class, 'forgotPassword'])
        ->middleware('throttle:password-reset');

    // ✅ Reset Password: 3 طلبات/15 دقيقة
    Route::post('reset-password', [AuthController::class, 'resetPassword'])
        ->middleware('throttle:password-reset');
    // ✅ send-otp: 5 محاولات/دقيقة
    Route::post('send-otp', [AuthController::class, 'sendOtp'])
        ->middleware('throttle:login');

    // ✅ verify-otp: 5 محاولات/دقيقة
    Route::post('verify-otp', [AuthController::class, 'verifyOtp'])
        ->middleware('throttle:login');

    Route::get('otp-logs', [\App\Http\Controllers\Admin\OtpLogController::class, 'index']);
});

// ============================================================
// Stripe Webhook - بدون auth لكن بتحقق من التوقيع داخل Controller
// ✅ بدون SanitizeInput (يحتاج raw body)
// ✅ بدون throttle (Stripe يرسل من IPs محددة)
// ============================================================
Route::post(
    'admin/payments/webhook',
    [\App\Http\Controllers\Admin\PaymentController::class, 'handleWebhook']
)->withoutMiddleware([
    \App\Http\Middleware\SanitizeInputMiddleware::class,
    \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
]);

// ============================================================
// Protected Routes (تحتاج auth:sanctum)
// ✅ throttle:api = 60 طلب/دقيقة لكل مستخدم
// ============================================================
Route::prefix('admin')->middleware(['auth:sanctum', 'throttle:api'])->group(function () {

    // ── Auth ────────────────────────────────────────────────
    Route::post('logout',  [AuthController::class, 'logout']);
    Route::get('me',       [AuthController::class, 'me']);
    Route::post('refresh', [AuthController::class, 'refresh']);

    // ── Profile ─────────────────────────────────────────────
    Route::get('/me',               [UserController::class, 'profile']);
    Route::put('/profile',          [UserController::class, 'updateProfile']);
    Route::post('/change-password', [UserController::class, 'changePassword']);

    // ── Activity Logs ────────────────────────────────────────
    Route::get('/activity-logs', [ActivityLogController::class, 'index']);

    // ── Admin Routes ─────────────────────────────────────────
    require __DIR__ . '/admin/clients.php';
    require __DIR__ . '/admin/invoices.php';
    require __DIR__ . '/admin/installments.php';
    require __DIR__ . '/admin/users.php';
    require __DIR__ . '/admin/dashboard.php';
    require __DIR__ . '/admin/permissions.php';
    require __DIR__ . '/admin/admin-groups.php';
    require __DIR__ . '/admin/payments.php';

    // ── Reports: throttle مخصص للـ Exports ──────────────────
    require __DIR__ . '/admin/reports.php';
});
