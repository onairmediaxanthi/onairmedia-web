<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class StaffInvite extends Mailable
{
    public function __construct(public User $user, public string $url, public ?User $by) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Πρόσκληση στη διαχείριση του '.\App\Models\Setting::get('company_name'));
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.staff-invite');
    }
}
