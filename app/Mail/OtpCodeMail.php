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
        return new Envelope(subject: $this->purpose === OtpPurpose::LOGIN
            ? 'ZYDEMLAK giriş doğrulama kodu'
            : 'ZYDEMLAK e-posta doğrulama kodu');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.otp-code');
    }
}
