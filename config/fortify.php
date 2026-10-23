<?php

use Laravel\Fortify\Features;

return [
    'guard' => 'web',
    'passwords' => 'users',
    'username' => 'email',
    'email' => 'email',
    'lowercase_usernames' => true,
    'home' => '/dashboard',
    'views' => false,
    'limiters' => ['login' => 'login', 'two-factor' => 'two-factor', 'passkeys' => 'passkeys'],
    'passkeys' => [
        'relying_party_id' => env('PASSKEY_RELYING_PARTY_ID', parse_url(env('APP_URL', 'http://localhost:8000'), PHP_URL_HOST)),
        'allowed_origins' => explode(',', env('PASSKEY_ALLOWED_ORIGINS', env('APP_URL', 'http://localhost:8000'))),
        'user_handle_secret' => env('APP_KEY'),
        'timeout' => 60000,
    ],
    // Enrollment is optional. Turning off a feature removes its routes and login challenge.
    'features' => array_values(array_filter([
        env('AUTH_TWO_FACTOR_ENABLED', true) ? Features::twoFactorAuthentication(['confirm' => true, 'confirmPassword' => true]) : null,
        env('AUTH_PASSKEYS_ENABLED', true) ? Features::passkeys(['confirmPassword' => true]) : null,
        Features::emailVerification(),
    ])),
];
