<?php

declare(strict_types=1);

return [
    'name' => 'SHASHE E GOVERNMENT',
    'url' => 'http://localhost/shashemene-egevornment',
    'timezone' => 'Africa/Addis_Ababa',
    'locale' => 'en',
    'supported_locales' => ['en', 'or', 'am'],
    'locale_names' => [
        'en' => 'English',
        'or' => 'Afaan Oromoo',
        'am' => 'አማርኛ',
    ],
    'session_name' => 'SHASHE_EGOV_SESSION',
    'session_lifetime' => 7200,
    'csrf_token_name' => '_csrf_token',
    'max_upload_size' => 10 * 1024 * 1024,
    'allowed_upload_types' => [
        'pdf' => 'application/pdf',
        'doc' => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
    ],
    'roles' => [
        'admin' => 1,
        'hr' => 2,
        'it' => 3,
        'employee' => 4,
    ],
    'brand' => [
        'primary' => '#F5B82E',
        'dark' => '#1a1a1a',
    ],
    
    // JWT Authentication Configuration
    'jwt' => [
        'secret' => getenv('JWT_SECRET') ?: base64_encode(random_bytes(32)),
        'algorithm' => 'HS256',
        'expiration' => 7200, // 2 hours in seconds
        'issuer' => 'shashe-egovernment',
    ],
    
    // API Configuration
    'api' => [
        'cors_origins' => [
            'http://localhost:3000',
            'http://localhost:5173',
            'http://localhost:8080',
        ],
        'cors_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
        'cors_headers' => ['Content-Type', 'Authorization'],
        'cors_credentials' => true,
        'rate_limiting' => [
            'enabled' => true,
            'requests_per_minute' => 100,
            'login_attempts_per_hour' => 5,
        ],
    ],
];
