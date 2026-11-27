<?php

return [

    'events' => [

        'LUCAS' => [

            'DEPOSIT_CREATED' => [
                'method' => 'POST',
                'endpoint' => '/api/v1/collections/test',
                //  http://127.0.0.1:9191/api/v1/collections/test
            ],

            'DEPOSIT_RESUBMITTED' => [
                'method' => 'PATCH',
                'endpoint' => '/api/v1/integrations/deposits/{id}',
            ],

            'RESTRUCTURING_REQUESTED' => [
                'method' => 'POST',
                'endpoint' => '/api/v1/integrations/lucas/request-restructuring',
            ],

            'EARLY_ENDORSEMENT_REQUESTED' => [
                'method' => 'POST',
                'endpoint' => '/api/v1/integrations/early-endorsed-accounts',
            ],

        ],

        'LMS' => [

            'CUSTOMER_UPDATED' => [
                'method' => 'PATCH',
                'endpoint' => '/api/v1/integrations/customers/{id}',
            ],

        ],

    ],

    'retry' => [
        'max_attempts' => 10,
        'delay_seconds' => 60,
        'max_delay_seconds' => 3600,
        'batch_size' => 50,
        'lease_seconds' => 60,
    ],

    'http' => [
        'connect_timeout' => 5,
        'timeout' => 15,
    ],

];
