<?php

use Laravel\Sanctum\Sanctum;

return [
    'stateful' => explode(',', env('SANCTUM_STATEFUL_DOMAINS', sprintf(
        '%s%s',
        'localhost,localhost:3000,127.0.0.1,127.0.0.1:8000,::1',
        Sanctum::currentApplicationUrlWithPort()
    ))),

    'guard' => ['web'],

    'expiration' => null,

    // ✅ إنهاء الجلسة تلقائياً بعد هذا العدد من الدقائق بدون أي نشاط
    // (يُستخدم بواسطة App\Http\Middleware\CheckIdleTimeout)
    'idle_timeout' => env('SANCTUM_IDLE_TIMEOUT', 60),

    'middleware' => [
        'verify_csrf_token' => App\Http\Middleware\VerifyCsrfToken::class,
        'encrypt_cookies' => App\Http\Middleware\EncryptCookies::class,
    ],
];
