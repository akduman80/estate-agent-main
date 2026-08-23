<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AccountDecisionMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public User $user, public string $decision, public ?string $note = null) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'ZYDEMLAK işletme üyeliği başvuru sonucu');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.account-decision');
    }
}
