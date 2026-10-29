<?php

namespace App\Support;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Cloudflare Turnstile («Δεν είμαι ρομπότ»): ενεργό μόλις συμπληρωθούν τα κλειδιά στο .env
 * (TURNSTILE_SITE_KEY, TURNSTILE_SECRET_KEY). Χωρίς κλειδιά ισχύουν οι υπόλοιπες προστασίες.
 */
final class Turnstile
{
    public static function siteKey(): ?string
    {
        return config('services.turnstile.site_key') ?: null;
    }

    public static function enabled(): bool
    {
        return self::siteKey() !== null && config('services.turnstile.secret_key');
    }

    public static function verify(?string $token, ?string $ip): bool
    {
        if (! self::enabled()) {
            return true;
        }
        if (! $token) {
            return false;
        }
        try {
            $r = Http::asForm()->timeout(8)->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
                'secret' => config('services.turnstile.secret_key'), 'response' => $token, 'remoteip' => $ip,
            ]);

            return (bool) $r->json('success');
        } catch (Throwable $e) {
            // Αν δεν απαντά το Cloudflare, δεν μπλοκάρουμε τον πελάτη (υπάρχουν και οι άλλες προστασίες)
            Log::warning('Turnstile unreachable: '.$e->getMessage());

            return true;
        }
    }
}
