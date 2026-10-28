<?php

namespace App\Services;

use App\Mail\CustomerInvite;
use App\Models\Business;
use App\Models\Quote;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

/** Πρόσκληση για πελάτες που άνοιξε το προσωπικό: ορίζουν κωδικό και αποδέχονται τους όρους */
class CustomerInvites
{
    public const VALID_DAYS = 14;

    public static function url(Business $business, ?Quote $quote = null): string
    {
        return URL::temporarySignedRoute('invite.accept', now()->addDays(self::VALID_DAYS),
            array_filter(['user' => $business->user_id, 'q' => $quote?->ulid]));
    }

    public function send(Business $business): void
    {
        $business->loadMissing('user');
        Mail::to($business->user->email)->send(new CustomerInvite($business, self::url($business)));
        $business->forceFill(['invited_at' => now()])->save();
        CustomerEvents::record('invited', $business->user, null);
    }
}
