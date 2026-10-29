<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Δοκιμαστικό email από τη διαχείριση, για έλεγχο παράδοσης */
class TestMail extends Mailable
{
    public function __construct(public string $sentBy) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Δοκιμαστικό email · '.\App\Models\Setting::get('company_name'));
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.test');
    }
}
