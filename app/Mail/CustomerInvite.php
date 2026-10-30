<?php

namespace App\Mail;

use App\Models\Business;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class CustomerInvite extends Mailable
{
    public function __construct(public Business $business, public string $url) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Ο λογαριασμός σας στο On Air Media Group είναι έτοιμος');
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.customer-invite');
    }
}
