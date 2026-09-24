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
        'gsm_no' => env('EIDS_GSM_NO'),
        'base_url' => env('EIDS_BASE_URL', 'https://ws.gtb.gov.tr:8443'),
        'verify_path' => env('EIDS_VERIFY_PATH', '/EidsTasinmazAPI'),
        'user_code_path' => env('EIDS_USER_CODE_PATH', '/EidsApi/Kullanici/GetKullaniciKodu'),
        'health_path' => env('EIDS_HEALTH_PATH', '/EidsApi/health'),
        'sso_url' => env('EIDS_SSO_URL', 'https://eids.ticaret.gov.tr/oturum'),
        'callback_url' => env('EIDS_CALLBACK_URL'),
        'timeout' => (int) env('EIDS_TIMEOUT', 20),
    ],

    'phone_otp' => [
        // Gerçek sağlayıcı bilgileri supervisor panelinde şifreli saklanır.
        // Ayarlar eksikken yerel geliştirmede güvenli log sürücüsü kullanılır.
        'driver' => env('PHONE_OTP_DRIVER', 'log'),
    ],

    'github_deploy' => [
        'secret' => env('GITHUB_DEPLOY_SECRET'),
        'branch' => env('GITHUB_DEPLOY_BRANCH', 'main'),
        'service' => env('GITHUB_DEPLOY_SERVICE', 'estate-agent-deploy.service'),
    ],

    'market_data' => [
        'enabled' => (bool) env('MARKET_DATA_ENABLED', true),
        'tcmb_url' => env('TCMB_RATES_URL', 'https://www.tcmb.gov.tr/kurlar/today.xml'),
        'gold_url' => env('GOLD_API_URL', 'https://api.gold-api.com'),
    ],

    'zyd_mobile' => [
        'publish_token' => env('ZYD_MOBILE_PUBLISH_TOKEN', env('ZYD_PUBLISH_TOKEN')),
    ],

    'google_maps' => [
        'browser_key' => env('GOOGLE_MAPS_BROWSER_KEY'),
    ],

];
