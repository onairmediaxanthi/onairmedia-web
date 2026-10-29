<?php

namespace App\Services\Quotes;

use App\Models\AdSlot;
use App\Models\Screen;
use App\Models\Setting;
use App\Models\Station;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * Ποιος τιμοκατάλογος ισχύει και πόσο κοστίζει κάθε μονάδα.
 *  local: ο βασικός (σποτ με τιμή ανά διάρκεια βάσης)
 *  metro: μεγάλα αστικά κέντρα (σποτ με τιμή ανά δευτερόλεπτο, δικές του τιμές για όλα τα υπόλοιπα)
 * Πελάτης με πόλη μέσα στη λίστα των ρυθμίσεων βλέπει ΑΠΟΚΛΕΙΣΤΙΚΑ τον metro.
 */
final class PriceBook
{
    public const TIERS = ['local' => 'Βασικός τιμοκατάλογος', 'metro' => 'Μεγάλα αστικά κέντρα'];

    public function __construct(public readonly string $tier = 'local') {}

    public static function for(?User $user): self
    {
        return new self(self::tierFor($user));
    }

    public static function tierFor(?User $user): string
    {
        $city = $user?->isCustomer() ? $user->business?->city : null;

        return $city && self::isMetroCity($city) ? 'metro' : 'local';
    }

    /** «Αθήνα», «ΑΘΗΝΑ», «Αθηνα», «athina» → ίδια πόλη */
    public static function normalizeCity(string $city): string
    {
        $s = mb_strtolower(trim($city));
        $s = strtr($s, ['ά' => 'α', 'έ' => 'ε', 'ή' => 'η', 'ί' => 'ι', 'ϊ' => 'ι', 'ΐ' => 'ι', 'ό' => 'ο', 'ύ' => 'υ', 'ϋ' => 'υ', 'ΰ' => 'υ', 'ώ' => 'ω', 'ς' => 'σ']);
        $s = strtr($s, ['θ' => 'th', 'χ' => 'ch', 'ψ' => 'ps', 'ξ' => 'x', 'ου' => 'ou', 'α' => 'a', 'β' => 'v', 'γ' => 'g', 'δ' => 'd', 'ε' => 'e', 'ζ' => 'z',
            'η' => 'i', 'ι' => 'i', 'κ' => 'k', 'λ' => 'l', 'μ' => 'm', 'ν' => 'n', 'ο' => 'o', 'π' => 'p', 'ρ' => 'r', 'σ' => 's', 'τ' => 't', 'υ' => 'y', 'φ' => 'f', 'ω' => 'o']);

        return preg_replace('/[^a-z]/', '', $s);
    }

    /** @return list<string> */
    public static function metroCities(): array
    {
        return array_values(array_filter((array) Setting::get('metro_cities', []), 'is_string'));
    }

    public static function isMetroCity(string $city): bool
    {
        $c = self::normalizeCity($city);

        return $c !== '' && collect(self::metroCities())->contains(fn ($m) => self::normalizeCity($m) === $c);
    }

    public function isMetro(): bool
    {
        return $this->tier === 'metro';
    }

    // ---------------- Ραδιόφωνο ----------------

    /** Τιμή αναφοράς του σταθμού (βασικός: ανά σποτ βάσης · metro: ανά δευτερόλεπτο) */
    public function stationBase(Station $s): ?float
    {
        $v = $this->isMetro() ? $s->metro_price_second : $s->price_spot;

        return $v !== null && (float) $v > 0 ? (float) $v : null;
    }

    public function stationSellable(?Station $s, bool $staff = false): bool
    {
        return $s !== null && $s->is_active && ($staff || $s->is_bookable) && $this->stationBase($s) !== null;
    }

    public function spotUnit(Station $s, int $seconds, string $position = 'standard', bool $fixedHour = false): float
    {
        $pct = match ($position) {
            'first' => (float) $s->pct_first_position,
            'middle' => (float) $s->pct_middle_spot,
            'sandwich' => (float) $s->pct_sandwich,
            default => 0.0,
        } + ($fixedHour ? (float) $s->pct_fixed_hour : 0.0);

        if ($this->isMetro()) {
            $raw = (float) $s->metro_price_second * max(1, $seconds); // ανά δευτερόλεπτο
        } else {
            $base = max(1, (int) $s->spot_seconds ?: 30);
            $raw = (float) $s->price_spot * max(1, $seconds / $base);
        }

        return round($raw * (1 + $pct / 100), 2);
    }

    /** «4,00 € / 30″» ή «0,1500 € / δευτ.» */
    public function stationPriceLabel(Station $s): string
    {
        return $this->isMetro()
            ? number_format((float) $s->metro_price_second, 4, ',', '.').' € / δευτ.'
            : QuoteCalculator::money($s->price_spot).' / '.((int) $s->spot_seconds ?: 30).'″';
    }

    public function mention(Station $s, string $kind): ?float
    {
        $v = $this->isMetro() ? $s->{'metro_mention_'.$kind} : $s->{'price_mention_'.$kind};

        return $v !== null ? (float) $v : null;
    }

    // ---------------- thrakitoday.gr, LED, λοιπά ----------------

    public function slotPrice(AdSlot $s): ?float
    {
        $v = $this->isMetro() ? $s->metro_price_per_day : $s->price_per_day;

        return $v !== null ? (float) $v : null;
    }

    public function screenPrice(Screen $s): ?float
    {
        $v = $this->isMetro() ? $s->metro_price_per_day : $s->price_per_day;

        return $v !== null ? (float) $v : null;
    }

    /** sponsored_article, article_writing, spot_production, … */
    public function extra(string $key): ?float
    {
        $v = Setting::get(($this->isMetro() ? 'metro_price_' : 'price_').$key);

        return $v !== null && $v !== '' ? (float) $v : null;
    }

    public function priceColumn(): string
    {
        return $this->isMetro() ? 'metro_price_per_day' : 'price_per_day';
    }

    /** Θέσεις/οθόνες με τιμή σε αυτόν τον τιμοκατάλογο */
    public function priced(Builder $query): Builder
    {
        return $query->whereNotNull($this->priceColumn());
    }
}
