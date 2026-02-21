<?php

return [

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

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

    // ✅ must be true when using credentials: "include"
    'supports_credentials' => true,

];