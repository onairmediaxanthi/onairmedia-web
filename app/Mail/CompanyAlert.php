<?php

namespace App\Mail;

use App\Models\CustomerActivity;
use App\Models\Quote;
use App\Models\User;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Ειδοποίηση προς την εταιρεία για ενέργεια πελάτη */
class CompanyAlert extends Mailable
{
    public function __construct(public string $type, public string $summary, public ?User $customer, public ?Quote $quote, public array $meta = []) {}

    public function envelope(): Envelope
    {
        $replyTo = $this->customer?->email ? [$this->customer->email] : [];

        return new Envelope(subject: '['.(CustomerActivity::TYPES[$this->type] ?? $this->type).'] '.$this->summary, replyTo: $replyTo);
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.company-alert', with: [
            'label' => CustomerActivity::TYPES[$this->type] ?? $this->type,
            'business' => $this->customer?->business,
            'order' => $order = $this->quote?->order,
            'adminUrl' => $order ? \App\Filament\Resources\Orders\OrderResource::getUrl('view', ['record' => $order], panel: 'admin')
                : ($this->quote ? \App\Filament\Resources\Quotes\QuoteResource::getUrl('view', ['record' => $this->quote], panel: 'admin') : url('/admin/drastiriotita')),
            'text' => $this->meta['text'] ?? null,
        ]);
    }
}
