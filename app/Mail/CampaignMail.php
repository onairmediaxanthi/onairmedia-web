<?php

namespace App\Mail;

use App\Models\Campaign;
use App\Models\Setting;
use App\Services\Quotes\QuotePresenter;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Support\Str;

/** Email μαζικής αποστολής, με διαγραφή ενός κλικ (List-Unsubscribe, RFC 8058) */
class CampaignMail extends Mailable
{
    public function __construct(public Campaign $campaign, public string $unsubscribeUrl, public ?string $greetingName = null) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->campaign->subject);
    }

    public function headers(): Headers
    {
        return new Headers(text: [
            'List-Unsubscribe' => '<'.$this->unsubscribeUrl.'>',
            'List-Unsubscribe-Post' => 'List-Unsubscribe=One-Click',
            'Precedence' => 'bulk',
        ]);
    }

    public function content(): Content
    {
        $c = $this->campaign;
        $cta = is_string($c->cta_url) && preg_match('#^(https://|/)#', $c->cta_url) ? (str_starts_with($c->cta_url, '/') ? url($c->cta_url) : $c->cta_url) : null;

        return new Content(view: 'mail.campaign', with: [
            'logo' => QuotePresenter::companyLogo('url'),
            'company' => Setting::allValues(),
            'html' => $c->body ? Str::sanitizeHtml($c->body) : null,
            'packages' => $c->packages(),
            'coupon' => $c->coupon && $c->coupon->isUsable() && ! $c->coupon->user_id ? $c->coupon : null,
            'cta' => $cta ? ['label' => $c->cta_label ?: 'Δείτε την προσφορά', 'url' => $cta] : ['label' => 'Βγάλτε προσφορά', 'url' => route('quote.builder')],
        ]);
    }
}
