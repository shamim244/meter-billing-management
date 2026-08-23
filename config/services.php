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

    'cashfree' => [
        'app_id' => env('CASHFREE_APP_ID', 'test_app_id'),
        'secret_key' => env('CASHFREE_SECRET_KEY', 'test_secret_key'),
        'environment' => env('CASHFREE_ENVIRONMENT', 'sandbox'), // 'sandbox' or 'production'
        'api_version' => env('CASHFREE_API_VERSION', '2023-08-01'),
        'webhook_secret' => env('CASHFREE_WEBHOOK_SECRET', 'test_webhook_secret'),
    ],

    'razorpay' => [
        'key_id' => env('RAZORPAY_KEY_ID', 'rzp_test_nbpdcl_saas'),
        'key_secret' => env('RAZORPAY_KEY_SECRET', 'test_razorpay_secret'),
        'webhook_secret' => env('RAZORPAY_WEBHOOK_SECRET', 'test_razorpay_webhook_secret'),
    ],

];
