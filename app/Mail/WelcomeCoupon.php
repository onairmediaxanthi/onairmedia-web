<?php

namespace App\Mail;

use App\Models\Coupon;
use App\Models\User;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class WelcomeCoupon extends Mailable
{
    public function __construct(public Coupon $coupon, public User $customer) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Δώρο καλωσορίσματος: '.$this->coupon->valueLabel().' έκπτωση στην πρώτη σας καμπάνια');
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.welcome-coupon', with: ['url' => route('quote.builder')]);
    }
}
