<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    // ✅ FIXED: Midtrans Configuration
    'midtrans' => [
        'merchant_id' => env('MIDTRANS_MERCHANT_ID'),
        'client_key' => env('MIDTRANS_CLIENT_KEY'),
        'server_key' => env('MIDTRANS_SERVER_KEY'),
        'is_production' => env('MIDTRANS_IS_PRODUCTION', false),
        'is_sanitized' => env('MIDTRANS_IS_SANITIZED', true),
        'is_3ds' => env('MIDTRANS_IS_3DS', true),
    ],

    // ✅ FIXED: RajaOngkir Configuration
    'rajaongkir' => [
        'api_key' => env('RAJAONGKIR_API_KEY', '8MZVaA6pc8c11707407345e5Ad0DK9eU'),
        'api_version' => env('RAJAONGKIR_API_VERSION', 'v2'),
        'base_url' => env('RAJAONGKIR_BASE_URL', 'https://rajaongkir.komerce.id/api/v1'),
        'auto_origin' => env('RAJAONGKIR_AUTO_ORIGIN', true),
        'auto_couriers' => env('RAJAONGKIR_AUTO_COURIERS', true),
        'timeout' => env('RAJAONGKIR_TIMEOUT', 25),
        'cache_duration' => env('RAJAONGKIR_CACHE_DURATION', 3600),
        'default_weight' => env('RAJAONGKIR_DEFAULT_WEIGHT', 1000),
        'supported_couriers' => [
            'jne', 'pos', 'tiki', 'wahana', 'sicepat', 'jet', 'dse', 'first',
            'ninja', 'lion', 'idl', 'rex', 'ide', 'sentral', 'anteraja',
            'lex', 'rpx', 'pandu', 'pahala', 'sap', 'jtr', 'dakota', 'star'
        ]
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI'),
    ],

    'google_maps' => [
        'api_key' => env('GOOGLE_MAPS_API_KEY'),
        'default_location' => [
            'lat' => env('STORE_DEFAULT_LAT', -6.2088),
            'lng' => env('STORE_DEFAULT_LNG', 106.8456),
        ],
        'default_zoom' => env('GOOGLE_MAPS_DEFAULT_ZOOM', 13),
        'country_restriction' => env('GOOGLE_MAPS_COUNTRY', 'ID'),
    ],

    'store' => [
        'name' => env('STORE_NAME', 'SneakerFlash'),
        'address' => env('STORE_ADDRESS'),
        'latitude' => env('STORE_LAT'),
        'longitude' => env('STORE_LNG'),
        'postal_code' => env('STORE_POSTAL_CODE'),
        'city' => env('STORE_CITY'),
        'province' => env('STORE_PROVINCE'),
        'rajaongkir_location_id' => env('STORE_RAJAONGKIR_LOCATION_ID'),
    ],
'ginee' => [
    'base'         => env('GINEE_API_URL', 'https://api.ginee.com'),
    'access_key'   => env('GINEE_ACCESS_KEY'),
    'secret_key'   => env('GINEE_SECRET_KEY'),
    'country'      => env('GINEE_COUNTRY', 'ID'),
    'warehouse_id' => env('GINEE_WAREHOUSE_ID'),
],
];