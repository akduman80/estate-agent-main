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

    'eids' => [
        'company_code' => env('EIDS_COMPANY_CODE'),
        'username' => env('EIDS_USERNAME'),
        'password' => env('EIDS_PASSWORD'),
        'user_code' => env('EIDS_USER_CODE'),
        'tax_no' => env('EIDS_TAX_NO'),
        'base_url' => env('EIDS_BASE_URL', 'https://ws.gtb.gov.tr:8443'),
        'verify_path' => env('EIDS_VERIFY_PATH', '/EidsTasinmazAPI'),
        'timeout' => (int) env('EIDS_TIMEOUT', 20),
    ],

];
