<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AccountApprovedMail extends Mailable
{
    use Queueable, SerializesModels;

    public User $user;

    public string $defaultPassword;

    public function __construct(User $user, string $defaultPassword = 'petugas_rspad123')
    {
        $this->user = $user;
        $this->defaultPassword = $defaultPassword;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Pemberitahuan Persetujuan Akun - SIMRS RSPAD Gatot Soebroto',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.account_approved',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
