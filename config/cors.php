<?php

return [

    // ✅ Include admin routes too
    'paths' => [
        'api/*',
        'admin/*',
        'sanctum/csrf-cookie',
        'login',
        'logout',
    ],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        'http://localhost:5173',
        'http://127.0.0.1:5173',
        'https://www.user.ashbhub.com',
        'https://user.ashbhub.com',
        'https://www.ashbhub.com',
        'https://ashbhub.com',
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 86400,

    // ✅ keep true if frontend sends cookies or credentials
    'supports_credentials' => true,

];