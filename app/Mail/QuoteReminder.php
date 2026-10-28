<?php

namespace App\Mail;

use App\Models\Quote;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Support\Facades\URL;

/** Υπενθύμιση για προσφορά που περιμένει στον λογαριασμό */
class QuoteReminder extends Mailable
{
    public function __construct(public Quote $quote) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Η προσφορά σας '.$this->quote->number.' σας περιμένει');
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.quote-reminder', with: [
            'url' => route('quotes.show', $this->quote),
            'stopUrl' => URL::signedRoute('quotes.reminders-off', $this->quote),
        ]);
    }
}
