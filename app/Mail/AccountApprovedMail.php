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
 * Mailable class untuk mengirimkan email pemberitahuan bahwa akun petugas telah disetujui oleh Admin.
 * Memuat kredensial login awal termasuk kata sandi default sistem.
 */
class AccountApprovedMail extends Mailable
{
    use Queueable, SerializesModels;

    public User $user;

    public string $defaultPassword;

    /**
     * Membuat instance pesan email baru.
     */
    public function __construct(User $user, string $defaultPassword = 'petugas_rspad123')
    {
        $this->user = $user;
        $this->defaultPassword = $defaultPassword;
    }

    /**
     * Mengonfigurasi amplop (envelope) email termasuk subjek email.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Pemberitahuan Persetujuan Akun - SIMRS RSPAD Gatot Soebroto',
        );
    }

    /**
     * Mengonfigurasi konten tampilan (template blade) email.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.account_approved',
        );
    }

    /**
     * Mengonfigurasi lampiran berkas email jika ada.
     *
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
