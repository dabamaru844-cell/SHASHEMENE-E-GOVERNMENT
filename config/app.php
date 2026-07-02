<?php

declare(strict_types=1);

return [
    'name' => 'SHASHE E GOVERNMENT',
    'url' => 'http://localhost/Shashemene%20e%20gevernment',
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
];
