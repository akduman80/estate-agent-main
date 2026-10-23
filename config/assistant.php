<?php

return [
    'enabled' => env('ASSISTANT_ENABLED', true),
    // Optional private intent gateway. No conversation or document contents are sent by default.
    'endpoint' => env('ASSISTANT_INTENT_ENDPOINT'),
    'key' => env('ASSISTANT_API_KEY'),
    'model' => env('ASSISTANT_MODEL'),
    'daily_limit' => 200,
];
