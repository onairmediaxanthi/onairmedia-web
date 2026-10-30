<?php

namespace App\Mail;

use App\Models\Order;
use App\Models\OrderEvent;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Ενημέρωση πελάτη για την πορεία της παραγγελίας του */
class OrderUpdated extends Mailable
{
    public function __construct(public Order $order, public OrderEvent $event) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->event->title.' · '.$this->order->number);
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.order-updated', with: [
            'url' => route('orders.show', $this->order),
            'accounts' => \App\Support\BankAccounts::all(),
            'showPayment' => $this->order->stage === 'awaiting_payment' && ! $this->order->isPaid(),
        ]);
    }
}
