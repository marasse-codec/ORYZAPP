<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MailService extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $message
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Alerte Oryza',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.template'
        );
    }
}