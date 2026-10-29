<?php

namespace App\Services\Quotes;

use App\Models\AdSlot;
use App\Models\Screen;
use App\Models\Setting;
use App\Models\Station;
use App\Support\Audience;
use Illuminate\Support\Collection;

/**
 * Από τις απαντήσεις του οδηγού βγάζει έως τρεις προτάσεις καμπάνιας μέσα στον προϋπολογισμό.
 *
 * 1. Βαθμολογεί τους σταθμούς με βάση το κοινό που ζητά ο πελάτης (ηλικίες, προφίλ, φύλο).
 * 2. Μοιράζει τον καθαρό προϋπολογισμό (χωρίς ΦΠΑ, μείον το δημιουργικό) σε μέσα με τρεις στρατηγικές:
 *    Στοχευμένη (1 σταθμός), Συνδυαστική (2 σταθμοί + site + LED), Μέγιστη κάλυψη (έως 4 σταθμοί + όλα).
 * 3. Υπολογίζει συχνότητα (μεταδόσεις/ημέρα) ανάλογα με τον στόχο και ό,τι περισσέψει το ξαναμοιράζει.
 * Η τελική τιμή κάθε πρότασης βγαίνει από τον QuoteCalculator, οπότε είναι ίδια με την προσφορά.
 */
class Recommender
{
    public const GOALS = [
        'awareness' => ['label' => 'Να με μάθουν', 'text' => 'Αναγνωρισιμότητα για την επιχείρηση, σταθερή παρουσία.', 'min' => 2, 'ideal' => 4],
        'offer' => ['label' => 'Προσφορά ή εκπτώσεις', 'text' => 'Μια προσφορά που πρέπει να ακουστεί γρήγορα.', 'min' => 3, 'ideal' => 6],
        'event' => ['label' => 'Εκδήλωση ή εγκαίνια', 'text' => 'Συγκεκριμένη ημερομηνία, χρειάζεται ένταση.', 'min' => 4, 'ideal' => 8],
        'hiring' => ['label' => 'Αναζητώ προσωπικό', 'text' => 'Αγγελία εργασίας που θέλει να τη δουν πολλοί.', 'min' => 2, 'ideal' => 4],
    ];

    public const MEDIA = ['radio' => 'Ραδιόφωνο', 'news' => 'thrakitoday.gr', 'led' => 'Πινακίδα LED'];

    public const MAX_SPOTS = 12;

    protected const STRATEGIES = [
        'focus' => ['title' => 'Στοχευμένη', 'stations' => 1, 'share' => ['radio' => 1.0, 'news' => 0, 'led' => 0],
            'text' => 'Όλος ο προϋπολογισμός στον σταθμό που ταιριάζει καλύτερα στο κοινό σας, με υψηλή συχνότητα.'],
        'combo' => ['title' => 'Συνδυαστική', 'stations' => 2, 'share' => ['radio' => 0.6, 'news' => 0.25, 'led' => 0.15],
            'text' => 'Ακούγεστε σε δύο σταθμούς και φαίνεστε στο site και στην πόλη: ο ίδιος πελάτης σας συναντά παντού.'],
        'wide' => ['title' => 'Μέγιστη κάλυψη', 'stations' => 4, 'share' => ['radio' => 0.7, 'news' => 0.15, 'led' => 0.15],
            'text' => 'Περισσότεροι σταθμοί με χαμηλότερη συχνότητα: φτάνετε σε όσο το δυνατόν περισσότερους ακροατές.'],
    ];

    public function __construct(protected QuoteCalculator $calc = new QuoteCalculator) {}

    public static function forStaff(): self
    {
        return new self(new QuoteCalculator(staff: true));
    }

    public static function forTier(string $tier, bool $staff = false): self
    {
        return new self(new QuoteCalculator(staff: $staff, tier: $tier));
    }

    /**
     * @param  array{budget: float|int, days: int, goal: string, ages?: list<string>, profiles?: list<string>, gender?: ?string, media?: list<string>, material?: string, seconds?: int}  $a
     * @return array{proposals: list<array<string, mixed>>, min_budget: ?float}
     */
    public function recommend(array $a): array
    {
        $days = max(1, min(QuoteCalculator::MAX_DAYS, (int) $a['days']));
        $vat = QuoteCalculator::vatRate();
        $net = round(((float) $a['budget']) / (1 + $vat / 100), 2);
        $goal = self::GOALS[$a['goal']] ?? self::GOALS['awareness'];
        $media = array_values(array_intersect(array_keys(self::MEDIA), (array) ($a['media'] ?? [])) ?: array_keys(self::MEDIA));
        $seconds = max(10, min(60, (int) ($a['seconds'] ?? 30)));
        $needCreative = ($a['material'] ?? 'ready') === 'need';

        $stations = $this->rankStations($a);
        $book = $this->calc->book;
        $slots = $book->priced(AdSlot::query()->active())->get();
        $screen = $book->priced(Screen::query()->active())->first();

        $proposals = [];
        $seen = [];
        foreach (self::STRATEGIES as $key => $strategy) {
            $specs = $this->build($strategy, $stations, $slots, $screen, $media, $net, $days, $goal, $seconds, $needCreative);
            if (! $specs) {
                continue;
            }
            $priced = $this->calc->price($specs, $days);
            $sig = md5(json_encode(QuoteCalculator::specsOf($priced['lines'])));
            if ($priced['subtotal'] <= 0 || isset($seen[$sig])) {
                continue;
            }
            $seen[$sig] = true;
            $proposals[] = [
                'key' => $key,
                'title' => $strategy['title'],
                'text' => $strategy['text'],
                'reasons' => $this->reasons($priced['lines'], $stations, $a),
                'specs' => QuoteCalculator::specsOf($priced['lines']),
                'priced' => $priced,
            ];
        }

        return ['proposals' => $proposals, 'min_budget' => $proposals ? null : $this->minimumBudget($stations, $days, $goal, $seconds)];
    }

    /** @return Collection<int, array{station: Station, score: float, unit: float}> */
    public function rankStations(array $a): Collection
    {
        $ages = (array) ($a['ages'] ?? []);
        $profiles = (array) ($a['profiles'] ?? []);
        $gender = $a['gender'] ?? null;
        $seconds = max(10, min(60, (int) ($a['seconds'] ?? 30)));

        return Station::query()->active()->get()
            ->filter(fn (Station $s) => $this->calc->book->stationSellable($s, $this->calc->staff))
            ->map(function (Station $s) use ($ages, $profiles, $gender, $seconds) {
                $aud = (array) $s->audience;
                $score = 1
                    + 2 * count(array_intersect($ages, (array) ($aud['ages'] ?? [])))
                    + 1.5 * count(array_intersect($profiles, (array) ($aud['profiles'] ?? [])));
                if ($gender && $gender !== 'balanced') {
                    $score += ($aud['gender'] ?? null) === $gender ? 1 : (($aud['gender'] ?? 'balanced') === 'balanced' ? 0.5 : 0);
                }

                return ['station' => $s, 'score' => $score, 'unit' => $this->calc->book->spotUnit($s, $seconds)];
            })
            ->sortBy([['score', 'desc'], [fn ($x) => $x['station']->sort, 'asc']])
            ->values();
    }

    protected function build(array $strategy, Collection $stations, Collection $slots, ?Screen $screen, array $media, float $net, int $days,
        array $goal, int $seconds, bool $needCreative): array
    {
        $book = $this->calc->book;
        // Ποια μέσα είναι πραγματικά διαθέσιμα σε αυτή τη στρατηγική
        $share = array_filter($strategy['share'], fn ($v, $k) => $v > 0 && in_array($k, $media, true)
            && ($k !== 'radio' || $stations->isNotEmpty())
            && ($k !== 'news' || $slots->isNotEmpty())
            && ($k !== 'led' || $screen !== null), ARRAY_FILTER_USE_BOTH);
        if (! $share) {
            // π.χ. μόνο site/LED επιλεγμένα στη «Στοχευμένη»: δίνουμε όλα στο πρώτο διαθέσιμο μέσο
            $first = collect(['radio' => $stations->isNotEmpty(), 'news' => $slots->isNotEmpty(), 'led' => $screen !== null])
                ->filter(fn ($ok, $k) => $ok && in_array($k, $media, true))->keys()->first();
            if (! $first) {
                return [];
            }
            $share = [$first => 1.0];
        }
        $sum = array_sum($share);
        $share = array_map(fn ($v) => $v / $sum, $share);

        $specs = [];
        $creative = 0.0;
        if ($needCreative) {
            foreach (['radio' => 'spot_production', 'led' => 'led_graphic', 'news' => 'banner_design'] as $m => $key) {
                if (isset($share[$m]) && ($p = $this->calc->book->extra($key)) !== null) {
                    $specs[] = ['type' => 'creative', 'key' => $key];
                    $creative += (float) $p;
                }
            }
        }
        $budget = $net - $creative;
        if ($budget <= 0) {
            return [];
        }

        // Ραδιόφωνο
        $radio = [];
        if (isset($share['radio'])) {
            $pool = $budget * $share['radio'];
            $n = min($strategy['stations'], $stations->count());
            while ($n > 0) {
                $picked = $stations->take($n);
                $perStation = $pool / $n;
                $radio = $picked->map(fn ($x) => [
                    'x' => $x, 'spots' => min(self::MAX_SPOTS, (int) floor($perStation / ($x['unit'] * $days))),
                ])->all();
                if (collect($radio)->every(fn ($r) => $r['spots'] >= $goal['min'])) {
                    break;
                }
                $n--;
                $radio = [];
            }
            // Ακόμα κι αν δεν φτάνει η ιδανική συχνότητα, 1 σταθμός με όσες χωράνε (≥1)
            if (! $radio) {
                $x = $stations->first();
                $spots = min(self::MAX_SPOTS, (int) floor($pool / ($x['unit'] * $days)));
                if ($spots >= 1) {
                    $radio = [['x' => $x, 'spots' => $spots]];
                }
            }
        }

        // thrakitoday.gr: η πιο προβεβλημένη θέση που χωράει
        $banner = null;
        if (isset($share['news'])) {
            $pool = $budget * $share['news'];
            $fits = $slots->filter(fn (AdSlot $s) => $book->slotPrice($s) * max($days, $s->min_days) <= $pool)->sortByDesc(fn ($s) => $book->slotPrice($s));
            if ($s = $fits->first()) {
                $banner = ['slot' => $s, 'days' => max($days, $s->min_days)];
            } elseif ($s = $slots->sortBy(fn ($s) => $book->slotPrice($s))->first()) {
                $d = (int) floor($pool / $book->slotPrice($s));
                if ($d >= max(1, $s->min_days)) {
                    $banner = ['slot' => $s, 'days' => $d];
                }
            }
        }

        // Πινακίδα LED
        $led = null;
        if (isset($share['led']) && $screen) {
            $d = min($days, (int) floor($budget * $share['led'] / $book->screenPrice($screen)));
            if ($d >= max(1, $screen->min_days)) {
                $led = $d;
            }
        }

        // Ό,τι περίσσεψε: περισσότερες μεταδόσεις, μέχρι την ιδανική και μετά μέχρι το όριο
        $spent = collect($radio)->sum(fn ($r) => $r['spots'] * $r['x']['unit'] * $days)
            + ($banner ? $book->slotPrice($banner['slot']) * $banner['days'] : 0)
            + ($led ? $book->screenPrice($screen) * $led : 0);
        $left = $budget - $spent;
        foreach ([$goal['ideal'], self::MAX_SPOTS] as $cap) {
            $added = true;
            while ($added && $radio) {
                $added = false;
                foreach ($radio as $i => $r) {
                    $cost = $r['x']['unit'] * $days;
                    if ($r['spots'] < $cap && $cost <= $left) {
                        $radio[$i]['spots']++;
                        $left -= $cost;
                        $added = true;
                    }
                }
            }
        }

        foreach ($radio as $r) {
            $specs[] = ['type' => 'radio', 'station_id' => $r['x']['station']->id, 'spots_per_day' => $r['spots'],
                'seconds' => $seconds, 'position' => 'standard', 'fixed_hour' => false, 'days' => $days];
        }
        if ($banner) {
            $specs[] = ['type' => 'banner', 'slot_id' => $banner['slot']->id, 'days' => $banner['days']];
        }
        if ($led) {
            $specs[] = ['type' => 'led', 'screen_id' => $screen->id, 'days' => $led];
        }

        // Χωρίς κανένα μέσο δεν είναι πρόταση (μόνο δημιουργικό)
        return ($radio || $banner || $led) ? $specs : [];
    }

    /** @return list<string> */
    protected function reasons(array $lines, Collection $stations, array $a): array
    {
        $out = [];
        $ids = array_filter(array_map(fn ($l) => $l['spec']['station_id'] ?? null, $lines));
        foreach ($stations->filter(fn ($x) => in_array($x['station']->id, $ids, true)) as $x) {
            $aud = (array) $x['station']->audience;
            $match = array_merge(
                array_map(fn ($k) => Audience::GROUPS['ages']['options'][$k] ?? null, array_intersect((array) ($a['ages'] ?? []), (array) ($aud['ages'] ?? []))),
                array_map(fn ($k) => Audience::GROUPS['profiles']['options'][$k] ?? null, array_intersect((array) ($a['profiles'] ?? []), (array) ($aud['profiles'] ?? []))),
            );
            $match = array_values(array_filter($match));
            if ($match) {
                $out[] = $x['station']->name.': ακούνε '.implode(', ', array_slice($match, 0, 4));
            }
        }

        return $out;
    }

    protected function minimumBudget(Collection $stations, int $days, array $goal, int $seconds): ?float
    {
        $cheapest = $stations->sortBy('unit')->first();
        if (! $cheapest) {
            return null;
        }

        return round($cheapest['unit'] * $goal['min'] * $days * (1 + QuoteCalculator::vatRate() / 100), 0);
    }
}
