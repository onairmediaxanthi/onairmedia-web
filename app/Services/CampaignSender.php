<?php

namespace App\Services;

use App\Mail\CampaignMail;
use App\Models\Campaign;
use App\Models\CampaignRecipient;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

/**
 * Αποστολή σε δόσεις (π.χ. 20 το λεπτό) για να μην ξεπερνιέται το όριο του mail server
 * και να μη μαρκάρεται ως spam. Η συγκατάθεση ελέγχεται ξανά τη στιγμή της αποστολής.
 */
class CampaignSender
{
    public static function perMinute(): int
    {
        return max(1, min(200, (int) Setting::get('campaign_per_minute', 20)));
    }

    /** Ξεκινά: «φωτογραφίζει» τους παραλήπτες */
    public function start(Campaign $campaign): int
    {
        return DB::transaction(function () use ($campaign) {
            $n = 0;
            foreach ($campaign->audienceQuery()->with('user')->get() as $b) {
                CampaignRecipient::query()->firstOrCreate(
                    ['campaign_id' => $campaign->id, 'user_id' => $b->user_id],
                    ['email' => $b->user->email, 'token' => Str::random(48), 'status' => 'pending'],
                );
                $n++;
            }
            $campaign->forceFill(['status' => 'sending', 'started_at' => now()])->save();

            return $n;
        });
    }

    public static function unsubscribeUrl(CampaignRecipient $r): string
    {
        return route('campaign.unsubscribe', $r->token);
    }

    /** Μία δόση. Επιστρέφει πόσα στάλθηκαν. */
    public function tick(): int
    {
        foreach (Campaign::query()->where('status', 'scheduled')->where(fn ($q) => $q->whereNull('scheduled_at')->orWhere('scheduled_at', '<=', now()))->get() as $c) {
            $this->start($c);
        }

        $budget = self::perMinute();
        $sent = 0;
        foreach (Campaign::query()->where('status', 'sending')->orderBy('started_at')->get() as $campaign) {
            $batch = $campaign->recipients()->where('status', 'pending')->with('user.business')->limit($budget - $sent)->get();
            foreach ($batch as $r) {
                $b = $r->user?->business;
                // Ανακλήθηκε η συγκατάθεση στο μεταξύ; Δεν στέλνουμε.
                if (! $b || ! $b->marketing_consent_at || ! $r->user->hasVerifiedEmail()) {
                    $r->update(['status' => 'skipped', 'error' => 'Χωρίς συγκατάθεση πλέον']);

                    continue;
                }
                try {
                    Mail::to($r->email)->send(new CampaignMail($campaign, self::unsubscribeUrl($r), $b->contact_person));
                    $r->update(['status' => 'sent', 'sent_at' => now(), 'error' => null]);
                    $sent++;
                } catch (Throwable $e) {
                    $r->update(['status' => 'failed', 'error' => mb_substr($e->getMessage(), 0, 250)]);
                }
            }
            if (! $campaign->recipients()->where('status', 'pending')->exists()) {
                $campaign->forceFill(['status' => 'sent', 'finished_at' => now()])->save();
            }
            if ($sent >= $budget) {
                break;
            }
        }

        return $sent;
    }

    /** Δοκιμαστική αποστολή στο προσωπικό, χωρίς να μετρά ως παραλήπτης */
    public function test(Campaign $campaign, User $to): void
    {
        Mail::to($to->email)->send(new CampaignMail($campaign, url('/aposyndromi/dokimi'), $to->name));
    }
}
