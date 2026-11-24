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
            'client_id' => env('LUCAS_CLIENT_ID', 'lucas'),
            'keys' => env('LUCAS_CLIENT_KEY_ID'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Request Time Tolerance
    |--------------------------------------------------------------------------
    |
    | The seconds an X-Request-Time header may differ from the server time, in
    | either direction. A nonce is remembered for twice this tolerance plus one
    | minute, so a request cannot be replayed after its nonce is forgotten.
    |
    */
    'request_time_tolerance' => (int) env('HMAC_REQUEST_TIME_TOLERANCE', 300),

    'query' => [
        'max_length' => (int) env('HMAC_QUERY_MAX_LENGTH', 1024),
        'max_parameters' => (int) env('HMAC_QUERY_MAX_PARAMETERS', 25),
        'max_nesting' => (int) env('HMAC_QUERY_MAX_NESTING', 2),
    ],

];
