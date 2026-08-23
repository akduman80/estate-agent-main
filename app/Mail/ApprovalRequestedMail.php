<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ApprovalRequestedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public string $approvalType, public User $user, public ?int $recordId = null) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'ZYDEMLAK - Onayınızı bekleyen '.$this->approvalType);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.approval-requested');
    }
}
