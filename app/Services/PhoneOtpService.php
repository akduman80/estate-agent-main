<?php

namespace App\Services;

use App\Contracts\PhoneOtpSender;
use App\Models\PhoneOtpCode;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class PhoneOtpService
{
    public function __construct(private readonly PhoneOtpSender $sender) {}

    public function issue(User $user, ?string $ipAddress): void
    {
        $settings = config('security.otp');
        $latest = PhoneOtpCode::query()->where('user_id', $user->id)->latest()->first();

        if ($latest && $latest->created_at->addSeconds($settings['resend_seconds'])->isFuture()) {
            throw ValidationException::withMessages(['code' => 'Yeni telefon kodu istemeden önce beklemelisiniz.']);
        }

        $count = PhoneOtpCode::query()->where('created_at', '>=', now()->subHour())
            ->where(fn ($query) => $query->where('user_id', $user->id)->orWhere('request_ip', $ipAddress))
            ->count();

        if ($count >= $settings['hourly_limit']) {
            throw ValidationException::withMessages(['code' => 'Saatlik telefon doğrulama sınırına ulaşıldı.']);
        }

        PhoneOtpCode::query()->where('user_id', $user->id)->whereNull('consumed_at')->update(['consumed_at' => now()]);
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        PhoneOtpCode::create([
            'user_id' => $user->id,
            'code_hash' => $this->hash($code),
            'expires_at' => now()->addMinutes($settings['ttl_minutes']),
            'request_ip' => $ipAddress,
        ]);
        try {
            $this->sender->send($user->phone, $code);
        } catch (\Throwable $exception) {
            PhoneOtpCode::query()->where('user_id', $user->id)->whereNull('consumed_at')->update(['consumed_at' => now()]);

            report($exception);

            throw ValidationException::withMessages([
                'code' => 'Telefon doğrulama kodu gönderilemedi. Lütfen daha sonra tekrar deneyin.',
            ]);
        }
    }

    public function verify(User $user, string $code): void
    {
        $otp = PhoneOtpCode::query()->where('user_id', $user->id)->whereNull('consumed_at')->latest()->first();

        if (! $otp || $otp->expires_at->isPast()) {
            throw ValidationException::withMessages(['code' => 'Kod geçersiz veya süresi dolmuş.']);
        }

        if ($otp->locked_until?->isFuture()) {
            throw ValidationException::withMessages(['code' => 'Telefon doğrulaması geçici olarak kilitlendi.']);
        }

        if (! hash_equals($otp->code_hash, $this->hash($code))) {
            $attempts = $otp->attempts + 1;
            $otp->update([
                'attempts' => $attempts,
                'locked_until' => $attempts >= config('security.otp.max_attempts') ? now()->addMinutes(config('security.otp.lock_minutes')) : null,
            ]);
            throw ValidationException::withMessages(['code' => 'Telefon doğrulama kodu hatalı.']);
        }

        $otp->update(['consumed_at' => now()]);
        $user->forceFill(['phone_verified_at' => now()])->save();
    }

    private function hash(string $code): string
    {
        return hash_hmac('sha256', $code, config('app.key'));
    }
}
