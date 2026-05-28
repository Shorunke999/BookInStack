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
    'onboarding_provider' => 'paystack', // or 'anchor'
    'anchor' => [
        'secret_key'     => env('ANCHOR_SECRET_KEY'),
        'webhook_secret' => env('ANCHOR_WEBHOOK_SECRET'),
        'env'            => env('ANCHOR_ENV', 'sandbox'),
    ],
    'paystack' => [
        'secret_key' => env('SECRET_PAYSTACK_API_KEY', 'sk_test_d7aab465384daeb89636150f185697f194f8c01d'),
    ],
    'ebulksms' => [
        'username' => env('EBULKSMS_USERNAME', 'mybusiness'),
        'apikey'   => env('EBULKSMS_APIKEY', 'myapikeys'),
        'sender'   => env('EBULKSMS_SENDER', 'BookinStack'),
    ],

];
