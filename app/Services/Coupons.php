<?php

namespace App\Services;

use App\Mail\WelcomeCoupon;
use App\Models\Coupon;
use App\Models\CouponRedemption;
use App\Models\Quote;
use App\Models\Setting;
use App\Models\User;
use App\Services\Quotes\QuoteCalculator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use InvalidArgumentException;
use Throwable;

/**
 * Κουπόνια: έλεγχος, εφαρμογή στο ταμείο, εξαργύρωση και δώρο καλωσορίσματος.
 * Ένα κουπόνι ανά προσφορά. Η έκπτωση μπαίνει ως γραμμή πριν τον ΦΠΑ.
 */
class Coupons
{
    /** Ποσό έκπτωσης ή εξήγηση γιατί δεν ισχύει */
    public function check(Coupon $coupon, User $user, Quote $quote): float
    {
        if (! $coupon->is_active || ($coupon->starts_at && $coupon->starts_at->isFuture())) {
            throw new InvalidArgumentException('Ο κωδικός δεν είναι ενεργός.');
        }
        if ($coupon->ends_at && $coupon->ends_at->isPast()) {
            throw new InvalidArgumentException('Ο κωδικός έληξε στις '.$coupon->ends_at->format('d/m/Y').'.');
        }
        if ($coupon->user_id && $coupon->user_id !== $user->id) {
            throw new InvalidArgumentException('Ο κωδικός δεν ισχύει για τον λογαριασμό σας.');
        }
        $others = fn ($q) => $q->where('quote_id', '!=', $quote->id);
        if ($coupon->usage_limit && $coupon->redemptions()->where($others)->count() >= $coupon->usage_limit) {
            throw new InvalidArgumentException('Ο κωδικός έχει ήδη χρησιμοποιηθεί το μέγιστο επιτρεπόμενο.');
        }
        if ($coupon->per_customer_limit && $coupon->redemptions()->where($others)->where('user_id', $user->id)->count() >= $coupon->per_customer_limit) {
            throw new InvalidArgumentException('Έχετε ήδη χρησιμοποιήσει αυτόν τον κωδικό.');
        }

        $base = $this->eligibleBase($quote, $coupon);
        if ($base <= 0) {
            throw new InvalidArgumentException('Ο κωδικός δεν ισχύει για έτοιμα πακέτα και προσφορές.');
        }
        if ($coupon->min_subtotal && $base < (float) $coupon->min_subtotal) {
            throw new InvalidArgumentException('Ο κωδικός ισχύει για καμπάνιες από '.QuoteCalculator::money($coupon->min_subtotal).' χωρίς ΦΠΑ.');
        }

        $amount = $coupon->type === 'percent' ? $base * (float) $coupon->value / 100 : (float) $coupon->value;
        if ($coupon->max_discount) {
            $amount = min($amount, (float) $coupon->max_discount);
        }

        return round(min($amount, $base), 2);
    }

    protected function eligibleBase(Quote $quote, Coupon $coupon): float
    {
        return round(collect((array) $quote->items)
            ->reject(fn ($l) => $l['type'] === 'coupon' || (! $coupon->applies_to_packages && $l['type'] === 'package'))
            ->sum('amount'), 2);
    }

    public function apply(Quote $quote, string $code, User $user): Coupon
    {
        $coupon = Coupon::query()->where('code', Coupon::normalize($code))->first();
        if (! $coupon) {
            throw new InvalidArgumentException('Ο κωδικός δεν βρέθηκε. Ελέγξτε ότι τον γράψατε σωστά.');
        }
        $amount = $this->check($coupon, $user, $quote);
        $this->setLine($quote, $coupon, $amount);
        CustomerEvents::record('coupon_applied', $user, $quote, ['code' => $coupon->code, 'amount' => $amount]);

        return $coupon;
    }

    public function remove(Quote $quote): void
    {
        $quote->items = array_values(array_filter((array) $quote->items, fn ($l) => $l['type'] !== 'coupon'));
        $quote->coupon_id = null;
        self::recalc($quote);
        $quote->save();
    }

    /** Μετά από αλλαγή τιμών: το κουπόνι ξαναϋπολογίζεται ή αφαιρείται αν δεν ισχύει πια */
    public function revalidate(Quote $quote): void
    {
        $coupon = $quote->coupon_id ? Coupon::query()->find($quote->coupon_id) : null;
        $quote->items = array_values(array_filter((array) $quote->items, fn ($l) => $l['type'] !== 'coupon'));
        if ($coupon && $quote->user) {
            try {
                $this->setLine($quote, $coupon, $this->check($coupon, $quote->user, $quote));

                return;
            } catch (InvalidArgumentException) {
            }
        }
        $quote->coupon_id = null;
        self::recalc($quote);
        $quote->save();
    }

    protected function setLine(Quote $quote, Coupon $coupon, float $amount): void
    {
        $items = array_values(array_filter((array) $quote->items, fn ($l) => $l['type'] !== 'coupon'));
        $items[] = [
            'ref' => null, 'type' => 'coupon', 'group' => 'Έκπτωση', 'label' => 'Κουπόνι '.$coupon->code.' ('.$coupon->valueLabel().')',
            'detail' => $coupon->name, 'unit_price' => -$amount, 'quantity' => 1, 'amount' => -$amount, 'spec' => null, 'includes' => [],
        ];
        $quote->items = $items;
        $quote->coupon_id = $coupon->id;
        self::recalc($quote);
        $quote->save();
    }

    public static function recalc(Quote $quote): void
    {
        $subtotal = round(collect((array) $quote->items)->sum('amount'), 2);
        $quote->subtotal = $subtotal;
        $quote->vat = round($subtotal * (float) $quote->vat_rate / 100, 2);
        $quote->total = round($subtotal + (float) $quote->vat, 2);
    }

    /** Στο ταμείο: τελικός έλεγχος και καταγραφή της χρήσης */
    public function redeem(Quote $quote): void
    {
        if (! $quote->coupon_id) {
            return;
        }
        DB::transaction(function () use ($quote) {
            $coupon = Coupon::query()->lockForUpdate()->find($quote->coupon_id);
            $amount = $coupon ? $this->check($coupon, $quote->user, $quote) : 0;
            CouponRedemption::query()->updateOrCreate(['quote_id' => $quote->id], ['coupon_id' => $coupon->id, 'user_id' => $quote->user_id, 'amount' => $amount]);
        });
    }

    /** Προσφορά που σβήνεται πριν επιβεβαιωθεί: το κουπόνι ξαναγίνεται διαθέσιμο */
    public function release(Quote $quote): void
    {
        if ($quote->status !== 'ordered') {
            CouponRedemption::query()->where('quote_id', $quote->id)->delete();
        }
    }

    /** Δώρο καλωσορίσματος: προσωπικός κωδικός μίας χρήσης, μόλις επιβεβαιωθεί το email */
    public function issueWelcome(User $user): ?Coupon
    {
        if (! Setting::get('welcome_coupon_enabled') || ! $user->isCustomer() || (float) Setting::get('welcome_coupon_value') <= 0
            || Coupon::query()->where('user_id', $user->id)->where('source', 'welcome')->exists()) {
            return null;
        }
        $days = max(1, (int) Setting::get('welcome_coupon_days', 30));
        $coupon = Coupon::query()->create([
            'code' => Coupon::generateCode((string) (Setting::get('welcome_coupon_prefix') ?: 'KALOS')),
            'name' => 'Δώρο καλωσορίσματος',
            'description' => 'Για την πρώτη σας καμπάνια.',
            'type' => Setting::get('welcome_coupon_type', 'percent') === 'fixed' ? 'fixed' : 'percent',
            'value' => (float) Setting::get('welcome_coupon_value'),
            'min_subtotal' => Setting::get('welcome_coupon_min') ?: null,
            'ends_at' => now()->addDays($days)->endOfDay(),
            'usage_limit' => 1, 'per_customer_limit' => 1, 'user_id' => $user->id, 'source' => 'welcome',
        ]);
        try {
            Mail::to($user->email)->send(new WelcomeCoupon($coupon, $user));
        } catch (Throwable $e) {
            Log::warning('Welcome coupon email failed: '.$e->getMessage());
        }
        CustomerEvents::record('coupon_issued', $user, null, ['code' => $coupon->code]);

        return $coupon;
    }
}
