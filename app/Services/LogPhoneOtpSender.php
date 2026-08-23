<?php

namespace App\Services;

use App\Contracts\PhoneOtpSender;
use Illuminate\Support\Facades\Log;

class LogPhoneOtpSender implements PhoneOtpSender
{
    public function send(string $phone, string $code): void
    {
        abort_if(app()->environment('production'), 503, 'Canlı telefon OTP sağlayıcısı henüz yapılandırılmadı.');

        Log::info('Yerel telefon doğrulama kodu', ['phone' => $phone, 'code' => $code]);
    }
}
