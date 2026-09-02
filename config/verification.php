<?php

return [
    'email' => [
        'development_bypass' => filter_var(env('EMAIL_VERIFICATION_DEV_BYPASS', false), FILTER_VALIDATE_BOOL),
        'mode' => env('EMAIL_VERIFICATION_MODE', 'random'),
        'static_code' => env('EMAIL_VERIFICATION_STATIC_CODE'),
        'code_length' => 6,
        'expires_minutes' => (int) env('EMAIL_VERIFICATION_EXPIRES_MINUTES', 10),
        'grant_expires_minutes' => (int) env('EMAIL_VERIFICATION_GRANT_EXPIRES_MINUTES', 10),
        'resend_cooldown_seconds' => (int) env('EMAIL_VERIFICATION_RESEND_COOLDOWN_SECONDS', 60),
        'max_attempts' => (int) env('EMAIL_VERIFICATION_MAX_ATTEMPTS', 5),
    ],
];
