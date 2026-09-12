<?php

namespace App\Services;

use App\Models\User;
use App\Models\Setting;

class OtpFeatureService
{
    public function emailEnabled(): bool
    {
        return filter_var(Setting::get('email_otp_enabled', false), FILTER_VALIDATE_BOOL);
    }

    public function phoneEnabled(): bool
    {
        return filter_var(Setting::get('phone_otp_enabled', false), FILTER_VALIDATE_BOOL);
    }

    public function isExempt(User $user): bool
    {
        return in_array(
            mb_strtolower(trim((string) $user->email)),
            array_map('mb_strtolower', config('security.otp.exempt_emails', [])),
            true,
        );
    }

    /**
     * OTP sınırlarını veritabanından alır; hatalı değerleri güvenli aralıklarda tutar.
     *
     * @return array{length: int, ttl_minutes: int, max_attempts: int, lock_minutes: int, resend_seconds: int, hourly_limit: int}
     */
    public function emailPolicy(): array
    {
        $defaults = config('security.otp');

        return [
            'length' => 6,
            'ttl_minutes' => $this->boundedInt('email_otp_ttl_minutes', $defaults['ttl_minutes'], 1, 30),
            'max_attempts' => $this->boundedInt('email_otp_max_attempts', $defaults['max_attempts'], 3, 10),
            'lock_minutes' => $this->boundedInt('email_otp_lock_minutes', $defaults['lock_minutes'], 1, 120),
            'resend_seconds' => $this->boundedInt('email_otp_resend_seconds', $defaults['resend_seconds'], 15, 600),
            'hourly_limit' => $this->boundedInt('email_otp_hourly_limit', $defaults['hourly_limit'], 1, 20),
        ];
    }

    private function boundedInt(string $key, int $default, int $minimum, int $maximum): int
    {
        return max($minimum, min($maximum, (int) Setting::get($key, $default)));
    }
}
