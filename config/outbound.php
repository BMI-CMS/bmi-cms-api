<?php

return [

    'lucas' => [
        'url' => env('LUCAS_BASE_URL'),
        'client_id' => env('LUCAS_CLIENT_ID', 'mobile-cms-api'),
        'key_id' => env('LUCAS_CLIENT_KEY_ID'),
        'secret' => env('LUCAS_CLIENT_SECRET'),
    ],

    'lms' => [
        'url' => env('LMS_BASE_URL'),
        'client_id' => env('LMS_CLIENT_ID', 'mobile-cms-api'),
        'key_id' => env('LMS_CLIENT_KEY_ID'),
        'secret' => env('LMS_CLIENT_SECRET'),
    ],

];
