<?php

return [
    'panels' => [
        'admin' => [
            'id' => 'admin',
            'path' => '/admin',
            'auth' => [
                'guard' => 'web',
                'provider' => 'users',
            ],
        ],
    ],
];