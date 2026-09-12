<?php

namespace App\Mail;

use App\Enums\OtpPurpose;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OtpCodeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public OtpPurpose $purpose,
        public string $code,
    ) {}

    public function envelope(): Envelope
    {
        $subject = match ($this->purpose) {
            OtpPurpose::LOGIN => 'ZYDEMLAK giriş doğrulama kodu',
            OtpPurpose::PASSWORD_RESET => 'ZYDEMLAK şifre sıfırlama kodu',
            default => 'ZYDEMLAK e-posta doğrulama kodu',
        };

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.otp-code');
    }
}
