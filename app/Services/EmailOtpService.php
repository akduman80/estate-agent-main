<?php

namespace App\Services;

use App\Enums\OtpPurpose;
use App\Mail\OtpCodeMail;
use App\Models\EmailOtpCode;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class EmailOtpService
{
    public function __construct(private readonly OtpFeatureService $otpFeatures) {}

    public function issue(User $user, OtpPurpose $purpose, ?string $ipAddress = null, ?string $targetEmail = null): void
    {
        $settings = $this->otpFeatures->emailPolicy();
        $lastCode = EmailOtpCode::query()
            ->where('user_id', $user->id)
            ->where('purpose', $purpose->value)
            ->latest()
            ->first();

        $resendAvailableAt = $lastCode?->created_at?->copy()->addSeconds($settings['resend_seconds']);

        if ($resendAvailableAt?->isFuture()) {
            $secondsUntilResend = max(1, $resendAvailableAt->getTimestamp() - now()->getTimestamp());

            throw ValidationException::withMessages([
                'code' => "Yeni kod zaten gönderildi. Tekrar göndermek için {$secondsUntilResend} saniye bekleyin.",
            ]);
        }

        $hourlyQuery = EmailOtpCode::query()
            ->where('created_at', '>=', now()->subHour())
            ->where(function ($query) use ($user, $ipAddress): void {
                $query->where('user_id', $user->id);

                if ($ipAddress) {
                    $query->orWhere('request_ip', $ipAddress);
                }
            });

        if ($hourlyQuery->count() >= $settings['hourly_limit']) {
            throw ValidationException::withMessages([
                'code' => 'Saatlik doğrulama kodu gönderim sınırına ulaşıldı. Lütfen daha sonra tekrar deneyin.',
            ]);
        }

        EmailOtpCode::query()
            ->where('user_id', $user->id)
            ->where('purpose', $purpose->value)
            ->whereNull('consumed_at')
            ->update(['consumed_at' => now()]);

        $code = str_pad((string) random_int(0, (10 ** $settings['length']) - 1), $settings['length'], '0', STR_PAD_LEFT);

        EmailOtpCode::create([
            'user_id' => $user->id,
            'purpose' => $purpose,
            'target_email' => $targetEmail,
            'code_hash' => $this->hash($code),
            'expires_at' => now()->addMinutes($settings['ttl_minutes']),
            'request_ip' => $ipAddress,
        ]);

        Mail::to($targetEmail ?: $user->email)->send(new OtpCodeMail($user, $purpose, $code));
    }

    public function verify(User $user, OtpPurpose $purpose, string $code): bool
    {
        $settings = $this->otpFeatures->emailPolicy();
        $otp = EmailOtpCode::query()
            ->where('user_id', $user->id)
            ->where('purpose', $purpose->value)
            ->whereNull('consumed_at')
            ->latest()
            ->first();

        if (! $otp || $otp->expires_at->isPast()) {
            throw ValidationException::withMessages(['code' => 'Kod geçersiz veya süresi dolmuş.']);
        }

        if ($otp->locked_until?->isFuture()) {
            throw ValidationException::withMessages(['code' => 'Çok fazla hatalı deneme yapıldı. Lütfen daha sonra tekrar deneyin.']);
        }

        if (! hash_equals($otp->code_hash, $this->hash($code))) {
            $attempts = $otp->attempts + 1;
            $updates = ['attempts' => $attempts];

            if ($attempts >= $settings['max_attempts']) {
                $updates['locked_until'] = now()->addMinutes($settings['lock_minutes']);
            }

            $otp->update($updates);

            throw ValidationException::withMessages(['code' => 'Doğrulama kodu hatalı.']);
        }

        $otp->update(['consumed_at' => now()]);

        return true;
    }

    private function hash(string $code): string
    {
        return hash_hmac('sha256', $code, config('app.key'));
    }
}
