<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Security Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains security-related configuration options for your
    | application. These settings help protect against common vulnerabilities.
    |
    */

    'file_upload' => [
        'max_size' => env('MAX_FILE_UPLOAD_SIZE', 2048), // KB
        'allowed_image_types' => ['jpeg', 'jpg', 'png'],
        'allowed_document_types' => ['csv', 'txt'],
        'scan_uploads' => env('SCAN_UPLOADS', false),
    ],

    'rate_limiting' => [
        'login_attempts' => env('LOGIN_RATE_LIMIT', 5),
        'exam_submissions' => env('EXAM_RATE_LIMIT', 3),
        'bulk_operations' => env('BULK_RATE_LIMIT', 2),
    ],

    'session' => [
        'encrypt' => env('SESSION_ENCRYPT', true),
        'secure_cookie' => env('SESSION_SECURE_COOKIE', env('APP_ENV') === 'production'),
        'same_site' => env('SESSION_SAME_SITE', 'lax'),
        'lifetime' => env('SESSION_LIFETIME', 120),
    ],

    'headers' => [
        'hsts_max_age' => env('HSTS_MAX_AGE', 31536000),
        'csp_enabled' => env('CSP_ENABLED', true),
        'frame_options' => env('X_FRAME_OPTIONS', 'DENY'),
    ],

    'password' => [
        'min_length' => env('PASSWORD_MIN_LENGTH', 8),
        'require_mixed_case' => env('PASSWORD_REQUIRE_MIXED_CASE', true),
        'require_numbers' => env('PASSWORD_REQUIRE_NUMBERS', true),
        'require_symbols' => env('PASSWORD_REQUIRE_SYMBOLS', false),
    ],
];