<?php

return [
    'id' => env('ADMIN_USER_ID'),
    'email' => env('ADMIN_EMAIL'),
    'display_name' => env('ADMIN_NAME', 'Administrator'),
    'password' => env('ADMIN_PASSWORD', env('AdminPassword')),
    'role' => 'superadmin',
];
