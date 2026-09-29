<?php

return [
    'default' => env('PAYMENT_PROVIDER', 'jazzcash'),
    'development_bypass' => filter_var(env('PAYMENT_DEV_BYPASS', false), FILTER_VALIDATE_BOOL),
    'mock' => [
        'enabled' => filter_var(env('MOCK_PAYMENTS_ENABLED', false), FILTER_VALIDATE_BOOL),
    ],
    'jazzcash' => [
        'endpoint' => env('JAZZCASH_ENDPOINT', 'https://sandbox.jazzcash.com.pk/CustomerPortal/transactionmanagement/merchantform/'),
        'merchant_id' => env('JAZZCASH_MERCHANT_ID'),
        'password' => env('JAZZCASH_PASSWORD'),
        'integrity_salt' => env('JAZZCASH_INTEGRITY_SALT'),
        'return_url' => env('JAZZCASH_RETURN_URL', rtrim(env('APP_URL', 'http://localhost:8000'), '/').'/api/v1/payments/callbacks/jazzcash'),
        'refund_endpoint' => env('JAZZCASH_REFUND_ENDPOINT'),
    ],
    'google_pay' => [
        'environment' => env('GOOGLE_PAY_ENVIRONMENT', 'TEST'),
        'merchant_id' => env('GOOGLE_PAY_MERCHANT_ID'),
        'merchant_name' => env('GOOGLE_PAY_MERCHANT_NAME', env('APP_NAME', 'Preppy')),
        'gateway' => env('GOOGLE_PAY_GATEWAY'),
        'gateway_merchant_id' => env('GOOGLE_PAY_GATEWAY_MERCHANT_ID'),
        'processor_url' => env('GOOGLE_PAY_PROCESSOR_URL'),
        'processor_secret' => env('GOOGLE_PAY_PROCESSOR_SECRET'),
        'refund_url' => env('GOOGLE_PAY_REFUND_URL'),
    ],
    'apple_iap' => [
        'enabled' => filter_var(env('APPLE_IAP_ENABLED', false), FILTER_VALIDATE_BOOL),
        'environment' => env('APPLE_IAP_ENVIRONMENT', 'auto'), // auto|production|sandbox
        'bundle_id' => env('APPLE_IAP_BUNDLE_ID'),
        'issuer_id' => env('APPLE_IAP_ISSUER_ID'),
        'key_id' => env('APPLE_IAP_KEY_ID'),
        'private_key_path' => env('APPLE_IAP_PRIVATE_KEY_PATH'),
    ],
];
