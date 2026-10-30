<?php

namespace App\Mail;

use App\Models\Quote;
use App\Services\Quotes\QuotePdf;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Επιβεβαίωση προς τον πελάτη ότι λάβαμε την παραγγελία */
class QuoteSubmitted extends Mailable
{
    public function __construct(public Quote $quote) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Λάβαμε την παραγγελία σας '.$this->quote->number);
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.quote-submitted', with: ['url' => $this->quote->order ? route('orders.show', $this->quote->order) : route('quotes.show', $this->quote)]);
    }

    public function attachments(): array
    {
        return [Attachment::fromData(fn () => QuotePdf::render($this->quote), QuotePdf::filename($this->quote))->withMime('application/pdf')];
    }
}
