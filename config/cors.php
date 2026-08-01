<?php

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie','broadcasting/*'],
    'allowed_methods' => ['*'],
    'allowed_origins' => ['http://localhost:5173','https://invoflow.it.com'],
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => false,
];
