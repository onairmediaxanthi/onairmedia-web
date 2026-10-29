<?php

namespace App\Mail;

use App\Models\CallbackRequest;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class CallbackRequested extends Mailable
{
    public function __construct(public CallbackRequest $cb) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: '[Να τον καλέσουμε] '.$this->cb->fullName().' · '.$this->cb->mobile);
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.callback-requested', with: ['business' => $this->cb->business]);
    }
}
