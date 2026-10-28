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
        if ($user->quotes()->where('status', 'saved')->count() >= self::MAX_OPEN_QUOTES) {
            throw new InvalidArgumentException('Έχετε ήδη '.self::MAX_OPEN_QUOTES.' ανοιχτές προσφορές. Σβήστε κάποιες παλιές για να αποθηκεύσετε νέα.');
        }

        $priced = $this->calc->price($specs, $days);
        if (! $priced['lines'] || $priced['subtotal'] <= 0) {
            throw new InvalidArgumentException('Η προσφορά δεν έχει καμία γραμμή με τιμή.');
        }

        $quote = new Quote([
            'title' => mb_substr(trim($title) ?: 'Η καμπάνια μου', 0, 120),
            'source' => array_key_exists($source, Quote::SOURCES) ? $source : 'manual',
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

    /** Νέες τιμές από τον τρέχοντα τιμοκατάλογο, νέα ημερομηνία λήξης */
    public function renew(Quote $quote): Quote
    {
        $priced = $this->calc->price($quote->specs(), $quote->days);
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

        CustomerEvents::record('quote_renewed', $quote->user, $quote);

        return $quote;
    }

    public function submit(Quote $quote, ?string $notes): Quote
    {
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
