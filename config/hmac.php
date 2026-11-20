<?php

return [
    'outbound' => [
        'lucas' => [
            'client_id' => env('LUCAS_CLIENT_ID', 'mobile-cms-api'),
            'key' => env('LUCAS_CLIENT_KEY_ID'),
            'secret' => env('LUCAS_CLIENT_SECRET_ID'),
            'url' => env('LUCAS_BASE_URL'),
        ],
    ],

    'inbound' => [
        'lucas' => [
            'client_id' => env('LUCAS_CLIENT_ID', 'mobile-cms-api'),
            'keys' => env('LUCAS_CLIENT_KEY_ID'),
        ],
    ],

];
