<?php

namespace App\Services\Quotes;

use App\Models\Quote;
use App\Models\Screen;
use App\Models\Setting;
use App\Models\Station;
use App\Support\Audience;
use App\Support\LogoPng;

/**
 * Οργανώνει μια προσφορά σε «μπλοκ» ανά μέσο (σταθμός, thrakitoday.gr, πινακίδα, λοιπά),
 * με λογότυπο και σύντομη περιγραφή, για το PDF και το email της πρότασης.
 */
class QuotePresenter
{
    /**
     * @param  'data'|'url'  $images  data URI για το PDF, URL για το email
     * @return list<array{key:string, title:string, subtitle:?string, text:?string, chips:list<string>, logo:?string, color:?string, lines:list<array>, total:float}>
     */
    public static function blocks(Quote $quote, string $images = 'data'): array
    {
        $img = fn (?string $path, int $w = 240) => $images === 'data' ? LogoPng::dataUri($path, $w) : LogoPng::url($path, $w);
        $lines = (array) $quote->items;

        // Παλιές προσφορές χωρίς ref: ταιριάζουμε σταθμό από το spec ή το όνομα
        $stations = Station::query()->get()->keyBy('id');
        foreach ($lines as $i => $l) {
            if (empty($l['ref'])) {
                if (in_array($l['type'], ['radio', 'mention'], true)) {
                    $sid = $l['spec']['station_id'] ?? $stations->first(fn ($s) => str_contains($l['label'], $s->name))?->id;
                    $lines[$i]['ref'] = $sid ? 'station:'.$sid : null;
                } elseif (in_array($l['type'], ['banner', 'article'], true)) {
                    $lines[$i]['ref'] = 'news';
                } elseif ($l['type'] === 'led') {
                    $lines[$i]['ref'] = 'screen:'.($l['spec']['screen_id'] ?? 0);
                }
            }
        }

        $blocks = [];
        foreach ($lines as $l) {
            if ($l['type'] === 'discount') {
                continue; // η έκπτωση φαίνεται στα σύνολα, όχι ως «μέσο»
            }
            $ref = $l['ref'] ?? null;
            $key = $ref ?: 'other:'.$l['type'];
            if (! isset($blocks[$key])) {
                $blocks[$key] = self::header($key, $l, $stations, $img);
            }
            $blocks[$key]['lines'][] = $l;
            $blocks[$key]['total'] += (float) $l['amount'];
        }

        // Σειρά: ραδιόφωνο, site, πινακίδα, πακέτα, δημιουργικό, έκπτωση
        $order = fn ($k) => match (true) {
            str_starts_with($k, 'station:') => 1, $k === 'news' => 2, str_starts_with($k, 'screen:') => 3,
            $k === 'other:package' => 4, $k === 'other:creative' => 5, $k === 'other:discount' => 9, default => 6,
        };
        uksort($blocks, fn ($a, $b) => $order($a) <=> $order($b));

        return array_values($blocks);
    }

    protected static function header(string $key, array $line, $stations, callable $img): array
    {
        $base = ['key' => $key, 'title' => $line['group'] ?? 'Λοιπά', 'subtitle' => null, 'text' => null, 'chips' => [], 'logo' => null,
            'color' => null, 'lines' => [], 'total' => 0.0, 'kind' => 'other'];

        if (str_starts_with($key, 'station:') && ($s = $stations->get((int) substr($key, 8)))) {
            $aud = Audience::labelsFor($s->audience);

            return ['title' => $s->name, 'subtitle' => trim(($s->frequency_label ?? '').($s->genre ? ' · '.$s->genre : ''), ' ·'),
                'text' => $s->slogan, 'chips' => array_slice(array_merge($aud['ages']['items'] ?? [], $aud['profiles']['items'] ?? []), 0, 6),
                'logo' => $img($s->logo_path), 'color' => $s->color, 'kind' => 'radio'] + $base;
        }
        if ($key === 'news') {
            return ['title' => 'thrakitoday.gr', 'subtitle' => 'Ειδήσεις της Θράκης', 'text' => 'Το ειδησεογραφικό site της περιοχής, κάθε μέρα στο κινητό και στον υπολογιστή.',
                'logo' => $img(Setting::get('logo_thrakitoday')), 'color' => '#121a33', 'kind' => 'news'] + $base;
        }
        if (str_starts_with($key, 'screen:')) {
            $sc = Screen::query()->find((int) substr($key, 7));

            return ['title' => $sc?->name ?? 'Πινακίδα LED', 'subtitle' => $sc?->location, 'text' => $sc?->description ? mb_strimwidth($sc->description, 0, 160, '…') : null,
                'logo' => $img(Setting::get('logo_led') ?: $sc?->photo_path), 'color' => '#ffb627', 'kind' => 'led'] + $base;
        }

        return ['title' => match ($line['type']) {
            'creative' => 'Δημιουργικό', 'package' => 'Πακέτο', 'discount' => 'Έκπτωση', default => $line['group'] ?? 'Λοιπά',
        }] + $base;
    }

    /** @return array{label:string, amount:float}|null */
    public static function discount(Quote $quote): ?array
    {
        $d = collect((array) $quote->items)->firstWhere('type', 'discount');

        return $d ? ['label' => $d['label'], 'amount' => (float) $d['amount']] : null;
    }

    /** Τα λογότυπα των μέσων της πρότασης, για τη «λωρίδα» στην αρχή */
    public static function mediaStrip(array $blocks): array
    {
        return array_values(array_filter($blocks, fn ($b) => in_array($b['kind'], ['radio', 'news', 'led'], true)));
    }

    public static function companyLogo(string $images = 'data'): ?string
    {
        $path = Setting::get('logo_light');

        return $images === 'data' ? LogoPng::dataUri($path, 600) : LogoPng::url($path, 600);
    }

    public static function intro(Quote $quote): string
    {
        if ($quote->intro) {
            return $quote->intro;
        }
        $name = $quote->user?->business?->contact_person;

        return ($name ? 'Αγαπητέ/ή '.$name.",\n\n" : '').'σας ετοιμάσαμε μια πρόταση προβολής για την επιχείρησή σας, με τα μέσα που ταιριάζουν στο κοινό σας. Θα χαρούμε να τη συζητήσουμε και να την προσαρμόσουμε όπως χρειάζεστε.';
    }
}
