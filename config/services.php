<?php

return [
    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI'),
    ],

    'apple_sign_in' => [
        'enabled' => env('APPLE_SIGN_IN_ENABLED', false),
        // Web: Apple Services ID. Native iOS: app Bundle ID.
        'client_id' => env('APPLE_SIGN_IN_CLIENT_ID'),
        'native_client_id' => env('APPLE_SIGN_IN_NATIVE_CLIENT_ID', env('APPLE_IAP_BUNDLE_ID')),
        'team_id' => env('APPLE_SIGN_IN_TEAM_ID'),
        'key_id' => env('APPLE_SIGN_IN_KEY_ID'),
        'private_key_path' => env('APPLE_SIGN_IN_PRIVATE_KEY_PATH'),
        'private_key' => env('APPLE_SIGN_IN_PRIVATE_KEY'),
        'redirect' => env('APPLE_SIGN_IN_REDIRECT_URI'),
    ],
];
