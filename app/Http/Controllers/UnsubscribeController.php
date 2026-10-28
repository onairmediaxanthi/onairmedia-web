<?php

namespace App\Http\Controllers;

use App\Models\CampaignRecipient;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

/**
 * Διαγραφή από τις προσφορές με ένα κλικ, χωρίς σύνδεση (άρθρο 11 ν. 3471/2006).
 * Το POST το στέλνουν και οι πάροχοι email (Gmail, Yahoo) μόνοι τους (RFC 8058).
 */
class UnsubscribeController extends Controller
{
    public function show(string $token): View
    {
        $r = $token === 'dokimi' ? null : CampaignRecipient::query()->where('token', $token)->firstOrFail();

        return view('campaign.unsubscribe', ['recipient' => $r, 'done' => (bool) $r?->unsubscribed_at, 'test' => $r === null]);
    }

    public function store(Request $request, string $token): View|Response
    {
        if ($token === 'dokimi') {
            return view('campaign.unsubscribe', ['recipient' => null, 'done' => true, 'test' => true]);
        }
        $r = CampaignRecipient::query()->where('token', $token)->with('user.business')->firstOrFail();
        if (! $r->unsubscribed_at) {
            $r->forceFill(['unsubscribed_at' => now()])->save();
            $r->user?->business?->setMarketingConsent(false, 'unsubscribe');
        }

        // Οι πάροχοι email θέλουν απλώς 200· ο άνθρωπος βλέπει σελίδα επιβεβαίωσης
        return $request->has('List-Unsubscribe')
            ? response('OK', 200)
            : view('campaign.unsubscribe', ['recipient' => $r, 'done' => true, 'test' => false]);
    }
}
