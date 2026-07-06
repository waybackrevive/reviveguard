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

    'uptime_kuma' => [
        'url'            => env('UPTIME_KUMA_URL', ''),
        'api_key'        => env('UPTIME_KUMA_API_KEY', ''),
        'webhook_secret' => env('UPTIME_KUMA_WEBHOOK_SECRET', ''),
    ],

    'puppeteer' => [
        'url' => env('PUPPETEER_SERVICE_URL', 'http://127.0.0.1:3002'),
    ],

    'resend' => [
        'api_key' => env('RESEND_API_KEY', ''),
        'from'    => env('MAIL_FROM_ADDRESS', 'notifications@reviveguard.com'),
    ],

    'stripe' => [
        'test_mode'          => env('STRIPE_TEST_MODE', false),
        'key'                => env('STRIPE_KEY', ''),
        'secret'             => env('STRIPE_SECRET', ''),
        'webhook_secret'     => env('STRIPE_WEBHOOK_SECRET', ''),
        'test_key'           => env('STRIPE_TEST_KEY', ''),
        'test_secret'        => env('STRIPE_TEST_SECRET', ''),
        'test_webhook_secret'=> env('STRIPE_TEST_WEBHOOK_SECRET', ''),
    ],

    'reviveguard' => [
        'plugin_download_url' => env('REVIVEGUARD_PLUGIN_DOWNLOAD_URL', ''),
        'api_url'             => env('REVIVEGUARD_API_URL', 'https://app.reviveguard.com'),
    ],

    'backblaze' => [
        'key_id'     => env('B2_KEY_ID', ''),
        'app_key'    => env('B2_APP_KEY', ''),
        'bucket_id'  => env('B2_BUCKET_ID', ''),
        'bucket_name'=> env('B2_BUCKET_NAME', ''),
    ],

    'whoisxml' => [
        'key' => env('WHOISXML_API_KEY', ''),
    ],

];
