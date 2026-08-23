<?php

return [
    'otp' => [
        'length' => 6,
        'ttl_minutes' => (int) env('EMAIL_OTP_TTL_MINUTES', 5),
        'max_attempts' => (int) env('EMAIL_OTP_MAX_ATTEMPTS', 5),
        'lock_minutes' => (int) env('EMAIL_OTP_LOCK_MINUTES', 15),
        'resend_seconds' => (int) env('EMAIL_OTP_RESEND_SECONDS', 60),
        'hourly_limit' => (int) env('EMAIL_OTP_HOURLY_LIMIT', 5),
    ],

    'approval_email' => env('ADMIN_APPROVAL_EMAIL', 'info@zydemlak.com.tr'),
];
