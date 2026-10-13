<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
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

    'pfaf' => [
        'export_path' => (fn (string $path) => str_starts_with($path, '/') ? $path : base_path($path))(
            env('PFAF_EXPORT_PATH', 'storage/app/private/imports/pfaf_plants_merged.csv')
        ),
    ],

    /*
     * Where browsers connect for live updates. In production this is the public
     * domain (443/https) while REVERB_HOST is the internal address the app uses.
     */
    'reverb_client' => [
        'host' => env('REVERB_CLIENT_HOST', env('REVERB_HOST')),
        'port' => (int) env('REVERB_CLIENT_PORT', env('REVERB_PORT', 8080)),
        'scheme' => env('REVERB_CLIENT_SCHEME', env('REVERB_SCHEME', 'http')),
    ],

    'ml' => [
        'url' => env('ML_SERVICE_URL', 'http://127.0.0.1:8001'),
        'token' => env('ML_SERVICE_TOKEN'),
        'timeout' => (int) env('ML_SERVICE_TIMEOUT', 60),
        'low_confidence_threshold' => (float) env('ML_LOW_CONFIDENCE_THRESHOLD', 0.35),
        'taxa_path' => env('ML_TAXA_PATH', base_path('ml-service/data/taxa.json')),
    ],

];
