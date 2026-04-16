<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AdminUserWelcomeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $recipientName,
        public string $identifier,
        public string $roleLabel,
        public string $portalUrl,
        public string $passwordSetupUrl,
        public string $statusLabel,
        public bool $requirePasswordReset,
        public bool $manualApprovalRequired,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Grade Dispute portal account'
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.admin-user-welcome'
        );
    }
}
