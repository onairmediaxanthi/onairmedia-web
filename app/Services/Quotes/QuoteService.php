<?php

namespace App\Services\Quotes;

use App\Mail\QuoteSubmitted;
use App\Models\Quote;
use App\Models\Setting;
use App\Models\User;
use App\Services\CustomerEvents;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use InvalidArgumentException;
use Throwable;

class QuoteService
{
    public const MAX_OPEN_QUOTES = 30;

    public function __construct(protected QuoteCalculator $calc = new QuoteCalculator) {}

    public static function validDays(): int
    {
        return max(1, min(90, (int) Setting::get('quote_valid_days', 15)));
    }

    /** Αποθηκεύει νέα προσφορά με τιμές που υπολογίζονται εδώ */
    public function create(User $user, string $title, string $source, array $specs, int $days, ?string $startsOn, array $answers = []): Quote
    {
        // Ο πελάτης δεν μπορεί να βάλει μόνος του έκπτωση, όπως κι αν φτάσει το αίτημα
        $specs = QuoteCalculator::customerSpecs($specs);

        if ($user->quotes()->where('status', 'saved')->count() >= self::MAX_OPEN_QUOTES) {
            throw new InvalidArgumentException('Έχετε ήδη '.self::MAX_OPEN_QUOTES.' ανοιχτές προσφορές. Σβήστε κάποιες παλιές για να αποθηκεύσετε νέα.');
        }

        $tier = PriceBook::tierFor($user);
        $priced = (new QuoteCalculator(tier: $tier))->price($specs, $days);
        if (! $priced['lines'] || $priced['subtotal'] <= 0) {
            throw new InvalidArgumentException('Η προσφορά δεν έχει καμία γραμμή με τιμή.');
        }

        $quote = new Quote([
            'title' => mb_substr(trim($title) ?: 'Η καμπάνια μου', 0, 120),
            'source' => array_key_exists($source, Quote::SOURCES) ? $source : 'manual',
            'price_tier' => $tier,
            'answers' => $answers ?: null,
            'starts_on' => self::parseStart($startsOn),
            'days' => max(1, min(QuoteCalculator::MAX_DAYS, $days)),
            'valid_until' => today()->addDays(self::validDays()),
        ] + $this->totals($priced));
        $quote->user()->associate($user);
        $quote->save();

        CustomerEvents::record('quote_saved', $user, $quote->refresh());

        return $quote;
    }

    /** Προσφορά που ετοιμάζει το προσωπικό για λογαριασμό πελάτη */
    public function createForCustomer(User $staff, User $customer, string $title, array $specs, int $days, ?string $startsOn, ?string $intro, array $answers = []): Quote
    {
        $tier = PriceBook::tierFor($customer);
        $priced = (new QuoteCalculator(staff: true, tier: $tier))->price($specs, $days);
        if (! $priced['lines'] || $priced['subtotal'] <= 0) {
            throw new InvalidArgumentException('Η προσφορά δεν έχει καμία γραμμή με τιμή.');
        }

        $quote = new Quote([
            'price_tier' => $tier,
            'title' => mb_substr(trim($title) ?: 'Πρόταση προβολής', 0, 120),
            'intro' => $intro ? mb_substr(trim($intro), 0, 3000) : null,
            'source' => 'staff',
            'answers' => $answers ?: null,
            'starts_on' => self::parseStart($startsOn),
            'days' => max(1, min(QuoteCalculator::MAX_DAYS, $days)),
            'valid_until' => today()->addDays(self::validDays()),
        ] + $this->totals($priced));
        $quote->user()->associate($customer);
        $quote->created_by_id = $staff->id;
        $quote->save();

        CustomerEvents::record('staff_quote', $customer, $quote->refresh(), ['by' => $staff->name]);

        return $quote;
    }

    /** Αλλαγές από το προσωπικό σε υπάρχουσα προσφορά: ίδιος αριθμός, νέες τιμές, νέα λήξη */
    public function updateByStaff(Quote $quote, User $staff, string $title, array $specs, int $days, ?string $startsOn, ?string $intro): Quote
    {
        $priced = (new QuoteCalculator(staff: true, tier: $quote->price_tier ?: 'local'))->price($specs, $days);
        if (! $priced['lines'] || $priced['subtotal'] <= 0) {
            throw new InvalidArgumentException('Η προσφορά δεν έχει καμία γραμμή με τιμή.');
        }
        $quote->fill([
            'title' => mb_substr(trim($title) ?: $quote->title, 0, 120),
            'intro' => $intro ? mb_substr(trim($intro), 0, 3000) : null,
            'starts_on' => self::parseStart($startsOn),
            'days' => max(1, min(QuoteCalculator::MAX_DAYS, $days)),
            'valid_until' => today()->addDays(self::validDays()),
        ] + $this->totals($priced));
        $quote->updated_by_id = $staff->id;
        if ($quote->status === 'expired') {
            $quote->status = 'saved';
        }
        $quote->save();
        app(\App\Services\Coupons::class)->revalidate($quote);

        CustomerEvents::record('quote_edited', $quote->user, $quote, ['by' => $staff->name]);

        return $quote;
    }

    /**
     * Ο πελάτης αλλάζει τη δική του προσφορά: ίδιος αριθμός, νέες τιμές, νέα λήξη.
     * Ό,τι είχε βάλει η ομάδα μας (έκπτωση, πακέτο, σταθμοί εκτός online) μένει όπως ήταν ($locked).
     */
    public function updateByCustomer(Quote $quote, string $title, array $specs, array $locked, int $days, ?string $startsOn): Quote
    {
        $specs = array_merge(QuoteCalculator::customerSpecs($specs), $locked);
        $staffTouched = $quote->created_by_id !== null || $quote->updated_by_id !== null;
        $priced = (new QuoteCalculator(staff: $staffTouched && $locked !== [], tier: $quote->price_tier ?: 'local'))->price($specs, $days);
        if (! $priced['lines'] || $priced['subtotal'] <= 0) {
            throw new InvalidArgumentException('Διαλέξτε τουλάχιστον ένα μέσο.');
        }
        $quote->fill([
            'title' => mb_substr(trim($title) ?: $quote->title, 0, 120),
            'starts_on' => self::parseStart($startsOn),
            'days' => max(1, min(QuoteCalculator::MAX_DAYS, $days)),
            'valid_until' => today()->addDays(self::validDays()),
        ] + $this->totals($priced));
        $quote->status = 'saved';
        $quote->reminders_sent = 0;
        $quote->last_reminded_at = null;
        $quote->save();
        app(\App\Services\Coupons::class)->revalidate($quote);

        CustomerEvents::record('quote_changed', $quote->user, $quote);

        return $quote;
    }

    /** Νέες τιμές από τον τρέχοντα τιμοκατάλογο, νέα ημερομηνία λήξης */
    public function renew(Quote $quote): Quote
    {
        $priced = (new QuoteCalculator(staff: $quote->created_by_id !== null || $quote->updated_by_id !== null, tier: $quote->price_tier ?: 'local'))->price($quote->specs(), $quote->days);
        if (! $priced['lines']) {
            throw new InvalidArgumentException('Τα μέσα αυτής της προσφοράς δεν είναι πια διαθέσιμα. Φτιάξτε μια νέα.');
        }
        $quote->fill($this->totals($priced) + ['valid_until' => today()->addDays(self::validDays())]);
        if ($quote->starts_on?->isPast()) {
            $quote->starts_on = null;
        }
        $quote->status = 'saved';
        $quote->reminders_sent = 0;
        $quote->last_reminded_at = null;
        $quote->save();
        app(\App\Services\Coupons::class)->revalidate($quote);

        CustomerEvents::record('quote_renewed', $quote->user, $quote);

        return $quote;
    }

    public function submit(Quote $quote, ?string $notes): Quote
    {
        app(\App\Services\Coupons::class)->redeem($quote); // πετάει εξήγηση αν το κουπόνι δεν ισχύει πια

        $quote->customer_notes = $notes ? mb_substr(trim($notes), 0, 2000) : null;
        $quote->status = 'submitted';
        $quote->submitted_at = now();
        $quote->save();

        try {
            Mail::to($quote->user->email)->send(new QuoteSubmitted($quote));
        } catch (Throwable $e) {
            Log::warning('Quote submitted email failed: '.$e->getMessage());
        }
        CustomerEvents::record('quote_submitted', $quote->user, $quote);

        return $quote;
    }

    public function delete(Quote $quote): void
    {
        CustomerEvents::record('quote_deleted', $quote->user, $quote);
        app(\App\Services\Coupons::class)->release($quote);
        $quote->delete();
    }

    protected function totals(array $priced): array
    {
        return [
            'items' => $priced['lines'],
            'subtotal' => $priced['subtotal'],
            'vat_rate' => $priced['vat_rate'],
            'vat' => $priced['vat'],
            'total' => $priced['total'],
        ];
    }

    public static function parseStart(?string $date): ?Carbon
    {
        if (! $date) {
            return null;
        }
        try {
            $d = Carbon::createFromFormat('Y-m-d', $date)->startOfDay();
        } catch (Throwable) {
            return null;
        }

        return $d->isBefore(today()) || $d->isAfter(today()->addYear()) ? null : $d;
    }
}
