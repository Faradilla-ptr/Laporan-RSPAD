<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Mailable class untuk mengirimkan email pemberitahuan bahwa pengajuan pendaftaran akun ditolak oleh Admin.
 */
class AccountRejectedMail extends Mailable
{
    use Queueable, SerializesModels;

    public User $user;

    /**
     * Membuat instance pesan email penolakan.
     */
    public function __construct(User $user)
    {
        $this->user = $user;
    }

    /**
     * Mengonfigurasi subjek dan amplop email.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Pemberitahuan Status Pendaftaran Akun - SIMRS RSPAD Gatot Soebroto',
        );
    }

    /**
     * Mengonfigurasi template tampilan blade untuk isi email.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.account_rejected',
        );
    }

    /**
     * Mengonfigurasi lampiran berkas email.
     *
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
