<?php

namespace App\Services\Quotes;

use App\Models\AdSlot;
use App\Models\Package;
use App\Models\Screen;
use App\Models\Setting;
use App\Models\Station;

/**
 * Τιμολογεί μια λίστα «γραμμών» καμπάνιας με τις τρέχουσες τιμές του τιμοκαταλόγου.
 * Η τιμή υπολογίζεται ΠΑΝΤΑ εδώ, στον server: ό,τι στέλνει ο browser είναι μόνο επιλογές.
 *
 * Γραμμές (spec):
 *  radio:    station_id, spots_per_day, seconds, position (standard|first|middle|sandwich), fixed_hour, mentions_live, mentions_recorded, days?
 *  banner:   slot_id, days?
 *  led:      screen_id, days?
 *  article:  count, writing
 *  creative: key (spot_production|spot_production_voices|led_graphic|banner_design)
 *  package:  package_id
 */
class QuoteCalculator
{
    public const POSITIONS = [
        'standard' => 'Κανονική θέση',
        'first' => 'Πάντα πρώτη θέση',
        'middle' => 'Ενδιάμεσο σποτ',
        'sandwich' => 'Sandwich',
    ];

    public const CREATIVE = [
        'spot_production' => 'Παραγωγή ραδιοφωνικού σποτ (1 φωνή)',
        'spot_production_voices' => 'Παραγωγή σποτ με διάλογο (2+ φωνές)',
        'led_graphic' => 'Γραφικό για πινακίδα LED',
        'banner_design' => 'Σχεδιασμός banner',
    ];

    public const MAX_DAYS = 365;

    public const MAX_SPOTS_PER_DAY = 24;

    /** @var array<int, Station> */
    protected array $stations = [];

    /** @var array<int, AdSlot> */
    protected array $slots = [];

    /** @var array<int, Screen> */
    protected array $screens = [];

    public function __construct()
    {
        $this->stations = Station::query()->where('is_active', true)->get()->keyBy('id')->all();
        $this->slots = AdSlot::query()->where('is_active', true)->get()->keyBy('id')->all();
        $this->screens = Screen::query()->where('is_active', true)->get()->keyBy('id')->all();
    }

    public static function vatRate(): float
    {
        return (float) Setting::get('vat_rate', 24);
    }

    /** Μπορεί ο σταθμός να μπει σε online προσφορά; */
    public static function stationSellable(?Station $s): bool
    {
        return $s !== null && $s->is_active && $s->is_bookable && $s->price_spot !== null && (float) $s->price_spot > 0;
    }

    /** «Star 88.8» αντί για «Star 88.8 88.8 FM» όταν το όνομα έχει ήδη τη συχνότητα */
    public static function stationLabel(Station $s): string
    {
        $freq = trim(str_replace('FM', '', (string) $s->frequency_label));

        return $freq !== '' && str_contains($s->name, $freq) ? $s->name : trim($s->name.' '.$s->frequency_label);
    }

    /** Τιμή μίας μετάδοσης με διάρκεια και θέση */
    public static function spotUnit(Station $s, int $seconds, string $position = 'standard', bool $fixedHour = false): float
    {
        $base = max(1, (int) $s->spot_seconds ?: 30);
        $lengthFactor = max(1, $seconds / $base);
        $pct = match ($position) {
            'first' => (float) $s->pct_first_position,
            'middle' => (float) $s->pct_middle_spot,
            'sandwich' => (float) $s->pct_sandwich,
            default => 0.0,
        } + ($fixedHour ? (float) $s->pct_fixed_hour : 0.0);

        return round((float) $s->price_spot * $lengthFactor * (1 + $pct / 100), 2);
    }

    /**
     * @param  list<array<string, mixed>>  $specs
     * @return array{lines: list<array<string, mixed>>, subtotal: float, vat_rate: float, vat: float, total: float, warnings: list<string>}
     */
    public function price(array $specs, int $days): array
    {
        $days = max(1, min(self::MAX_DAYS, $days));
        $lines = [];
        $warnings = [];

        foreach ($specs as $spec) {
            $type = $spec['type'] ?? null;
            $lineDays = max(1, min(self::MAX_DAYS, (int) ($spec['days'] ?? $days)));

            switch ($type) {
                case 'radio':
                    $st = $this->stations[(int) ($spec['station_id'] ?? 0)] ?? null;
                    if (! self::stationSellable($st)) {
                        $warnings[] = 'Ένας σταθμός δεν είναι διαθέσιμος online αυτή τη στιγμή και αφαιρέθηκε.';
                        break;
                    }
                    $spots = max(0, min(self::MAX_SPOTS_PER_DAY, (int) ($spec['spots_per_day'] ?? 0)));
                    $seconds = max(5, min(120, (int) ($spec['seconds'] ?? $st->spot_seconds ?? 30)));
                    $position = array_key_exists($spec['position'] ?? '', self::POSITIONS) ? $spec['position'] : 'standard';
                    $fixed = (bool) ($spec['fixed_hour'] ?? false);
                    $clean = ['type' => 'radio', 'station_id' => $st->id, 'spots_per_day' => $spots, 'seconds' => $seconds,
                        'position' => $position, 'fixed_hour' => $fixed, 'days' => $lineDays];

                    $stLines = [];
                    if ($spots > 0) {
                        $unit = self::spotUnit($st, $seconds, $position, $fixed);
                        $detail = $spots.' μεταδόσεις/ημέρα × '.$lineDays.' ημέρες · σποτ '.$seconds.'″';
                        if ($position !== 'standard') {
                            $detail .= ' · '.self::POSITIONS[$position];
                        }
                        if ($fixed) {
                            $detail .= ' · επιλεγμένες ώρες';
                        }
                        $stLines[] = $this->line('radio', self::stationLabel($st), $detail, $unit, $spots * $lineDays, null, 'Ραδιόφωνο');
                    }

                    foreach (['live' => 'Αναφορά από τον παραγωγό', 'recorded' => 'Έτοιμη εκφώνηση'] as $kind => $label) {
                        $n = max(0, min(self::MAX_SPOTS_PER_DAY, (int) ($spec['mentions_'.$kind] ?? 0)));
                        $price = $st->{'price_mention_'.$kind};
                        $clean['mentions_'.$kind] = $price === null ? 0 : $n;
                        if ($n > 0 && $price !== null) {
                            $stLines[] = $this->line('mention', $label.' · '.$st->name, $n.'/ημέρα × '.$lineDays.' ημέρες', (float) $price, $n * $lineDays, null, 'Ραδιόφωνο');
                        }
                    }

                    // Το spec του σταθμού (για επεξεργασία/ανανέωση) μένει στην πρώτη του γραμμή
                    if ($stLines) {
                        $stLines[0]['spec'] = $clean;
                        array_push($lines, ...$stLines);
                    }
                    break;

                case 'banner':
                    $slot = $this->slots[(int) ($spec['slot_id'] ?? 0)] ?? null;
                    if (! $slot || $slot->price_per_day === null) {
                        $warnings[] = 'Μια θέση banner δεν είναι διαθέσιμη και αφαιρέθηκε.';
                        break;
                    }
                    $d = max($lineDays, (int) $slot->min_days);
                    if ($d > $lineDays) {
                        $warnings[] = 'Η θέση «'.$slot->name.'» κλείνεται για τουλάχιστον '.$slot->min_days.' ημέρες.';
                    }
                    $lines[] = $this->line('banner', 'thrakitoday.gr · '.$slot->name, trim($slot->dimensions.' · '.$d.' ημέρες', ' ·'),
                        (float) $slot->price_per_day, $d, ['type' => 'banner', 'slot_id' => $slot->id, 'days' => $d], 'thrakitoday.gr');
                    break;

                case 'led':
                    $screen = $this->screens[(int) ($spec['screen_id'] ?? 0)] ?? null;
                    if (! $screen || $screen->price_per_day === null) {
                        $warnings[] = 'Μια πινακίδα LED δεν είναι διαθέσιμη και αφαιρέθηκε.';
                        break;
                    }
                    $d = max($lineDays, (int) $screen->min_days);
                    $detail = $d.' ημέρες · προβολή '.$screen->slot_seconds.'″'.($screen->loops_per_hour ? ' · '.$screen->loops_per_hour.' φορές/ώρα' : '');
                    $lines[] = $this->line('led', $screen->name, $detail, (float) $screen->price_per_day, $d,
                        ['type' => 'led', 'screen_id' => $screen->id, 'days' => $d], 'Πινακίδα LED');
                    break;

                case 'article':
                    $n = max(0, min(30, (int) ($spec['count'] ?? 0)));
                    $price = Setting::get('price_sponsored_article');
                    if ($n === 0 || $price === null) {
                        if ($n > 0) {
                            $warnings[] = 'Το χορηγούμενο άρθρο δεν διατίθεται online ακόμα.';
                        }
                        break;
                    }
                    $writing = (bool) ($spec['writing'] ?? false) && Setting::get('price_article_writing') !== null;
                    $lines[] = $this->line('article', 'Χορηγούμενο άρθρο στο thrakitoday.gr', $n === 1 ? '1 δημοσίευση' : $n.' δημοσιεύσεις',
                        (float) $price, $n, ['type' => 'article', 'count' => $n, 'writing' => $writing], 'thrakitoday.gr');
                    if ($writing) {
                        $lines[] = $this->line('creative', 'Συγγραφή άρθρου από την ομάδα μας', null, (float) Setting::get('price_article_writing'), $n, null, 'Δημιουργικό');
                    }
                    break;

                case 'creative':
                    $key = $spec['key'] ?? null;
                    $price = array_key_exists((string) $key, self::CREATIVE) ? Setting::get('price_'.$key) : null;
                    if ($price === null) {
                        break;
                    }
                    $lines[] = $this->line('creative', self::CREATIVE[$key], null, (float) $price, 1, ['type' => 'creative', 'key' => $key], 'Δημιουργικό');
                    break;

                case 'package':
                    $p = Package::query()->available()->whereKey((int) ($spec['package_id'] ?? 0))->first();
                    if (! $p || $p->price === null) {
                        $warnings[] = 'Το πακέτο δεν είναι πλέον διαθέσιμο.';
                        break;
                    }
                    $lines[] = $this->line('package', 'Πακέτο «'.$p->name.'»', $p->tagline ?: ($p->duration_days ? $p->duration_days.' ημέρες' : null),
                        (float) $p->price, 1, ['type' => 'package', 'package_id' => $p->id], 'Πακέτο', $p->includes ?: []);
                    break;
            }
        }

        $subtotal = round(array_sum(array_column($lines, 'amount')), 2);
        $rate = self::vatRate();
        $vat = round($subtotal * $rate / 100, 2);

        return [
            'lines' => $lines,
            'subtotal' => $subtotal,
            'vat_rate' => $rate,
            'vat' => $vat,
            'total' => round($subtotal + $vat, 2),
            'warnings' => array_values(array_unique($warnings)),
        ];
    }

    /** @param list<string> $includes */
    protected function line(string $type, string $label, ?string $detail, float $unit, int $qty, ?array $spec, string $group, array $includes = []): array
    {
        return [
            'type' => $type,
            'group' => $group,
            'label' => $label,
            'detail' => $detail,
            'unit_price' => round($unit, 2),
            'quantity' => $qty,
            'amount' => round($unit * $qty, 2),
            'spec' => $spec,
            'includes' => array_values(array_filter((array) $includes, 'is_string')),
        ];
    }

    /** Τα specs μιας τιμολογημένης λίστας (για ανανέωση με νέες τιμές) */
    public static function specsOf(array $lines): array
    {
        return array_values(array_filter(array_column($lines, 'spec')));
    }

    public static function money(float|string|null $v): string
    {
        return number_format((float) $v, 2, ',', '.').' €';
    }
}
