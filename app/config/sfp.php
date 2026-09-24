<?php

return [
    'initial_admin' => [
        'name' => env('SFP_ADMIN_NAME', 'Initial Admin'),
        'username' => env('SFP_ADMIN_USERNAME'),
        'password' => env('SFP_ADMIN_PASSWORD'),
    ],
    'demo' => [
        'enabled' => env('SFP_DEMO_ENABLED', false),
        'admin_username' => env('SFP_DEMO_ADMIN_USERNAME'),
        'admin_password' => env('SFP_DEMO_ADMIN_PASSWORD'),
        'staff_username' => env('SFP_DEMO_STAFF_USERNAME'),
        'staff_password' => env('SFP_DEMO_STAFF_PASSWORD'),
        'staff_whatsapp' => env('SFP_DEMO_STAFF_WHATSAPP'),
    ],
];
