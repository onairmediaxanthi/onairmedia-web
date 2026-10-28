<?php

namespace App\Mail;

use App\Models\Quote;
use App\Models\User;
use App\Services\CustomerInvites;
use App\Services\Quotes\QuotePdf;
use App\Services\Quotes\QuotePresenter;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Η πρόταση που στέλνει το προσωπικό στον πελάτη */
class QuoteProposal extends Mailable
{
    public function __construct(public Quote $quote, public string $subjectLine, public string $body, public bool $attachPdf, public ?User $sender) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->subjectLine,
            replyTo: $this->sender?->email ? [new Address($this->sender->email, $this->sender->name)] : [],
        );
    }

    public function content(): Content
    {
        $business = $this->quote->user?->business;
        // Πελάτης που δεν έχει ενεργοποιήσει λογαριασμό: το κουμπί τον ενεργοποιεί και τον πάει στην πρόταση
        $url = $business && ! $business->isActivated()
            ? CustomerInvites::url($business, $this->quote)
            : route('quotes.show', $this->quote);
        $blocks = QuotePresenter::blocks($this->quote, 'url');

        return new Content(view: 'mail.quote-proposal', with: [
            'business' => $business,
            'url' => $url,
            'needsActivation' => $business && ! $business->isActivated(),
            'blocks' => $blocks,
            'strip' => QuotePresenter::mediaStrip($blocks),
            'logo' => QuotePresenter::companyLogo('url'),
            'company' => \App\Models\Setting::allValues(),
        ]);
    }

    public function attachments(): array
    {
        return $this->attachPdf
            ? [Attachment::fromData(fn () => QuotePdf::render($this->quote), QuotePdf::filename($this->quote))->withMime('application/pdf')]
            : [];
    }
}
