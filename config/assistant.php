<?php

return [
    'enabled' => env('ASSISTANT_ENABLED', true),
    // Optional private intent gateway. No conversation or document contents are sent by default.
    'endpoint' => env('ASSISTANT_INTENT_ENDPOINT'),
    'key' => env('ASSISTANT_API_KEY'),
    'model' => env('ASSISTANT_MODEL'),
    'daily_limit' => 200,
    'gemini' => [
        'key' => env('GEMINI_API_KEY'),
        'model' => env('GEMINI_MODEL', 'gemini-3.5-flash-lite'),
        'timeout' => (int) env('GEMINI_TIMEOUT', 12),
        'max_output_tokens' => (int) env('GEMINI_MAX_OUTPUT_TOKENS', 1200),
        'user_daily_limit' => (int) env('GEMINI_USER_DAILY_LIMIT', 50),
        'server_daily_limit' => (int) env('GEMINI_SERVER_DAILY_LIMIT', 1000),
        'daily_token_budget' => (int) env('GEMINI_DAILY_TOKEN_BUDGET', 2000000),
        'input_usd_per_million' => env('GEMINI_INPUT_USD_PER_MILLION'),
        'output_usd_per_million' => env('GEMINI_OUTPUT_USD_PER_MILLION'),
    ],
];
