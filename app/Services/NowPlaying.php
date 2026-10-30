<?php

namespace App\Services;

use App\Models\Station;
use Illuminate\Http\Client\Pool;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * «Τι παίζει τώρα» από τα στατιστικά του live24.gr.
 * Ένα αίτημα για όλους τους σταθμούς, με cache 20 δευτερολέπτων,
 * ώστε οι επισκέπτες να μη χτυπούν απευθείας το live24.
 */
class NowPlaying
{
    public const ENDPOINT = 'https://songs.live24.gr/live24stats/songs_list.xsl';

    public const TTL_SECONDS = 20;

    /**
     * @return array<string, array{artist: ?string, title: ?string, raw: ?string}>
     */
    public function all(): array
    {
        return Cache::remember('now-playing:all', self::TTL_SECONDS, fn () => $this->fetch(
            Station::query()->active()->whereNotNull('now_playing_mount')->get(['slug', 'now_playing_mount'])
        ));
    }

    /**
     * Φρέσκια ανάγνωση (για την καταγραφή κάθε λεπτό). Ανανεώνει και την cache του player,
     * ώστε οι επισκέπτες να μην προκαλούν επιπλέον κλήσεις στο live24.
     *
     * @return array<string, array{artist: ?string, title: ?string, raw: ?string}>
     */
    public function fresh(): array
    {
        $all = $this->fetch(Station::query()->active()->whereNotNull('now_playing_mount')->get(['slug', 'now_playing_mount']));
        if ($all) {
            Cache::put('now-playing:all', $all, self::TTL_SECONDS);
        }

        return $all;
    }

    /**
     * @param  Collection<int, Station>  $stations
     * @return array<string, array{artist: ?string, title: ?string, raw: ?string}>
     */
    protected function fetch(Collection $stations): array
    {
        if ($stations->isEmpty()) {
            return [];
        }

        try {
            $responses = Http::pool(fn (Pool $pool) => $stations->map(
                fn (Station $s) => $pool->as($s->slug)
                    ->timeout(4)
                    ->withUserAgent('OnAirMedia/1.0 (+https://onairmedia.gr)')
                    ->get(self::ENDPOINT, ['mount' => $s->now_playing_mount])
            )->all());
        } catch (Throwable) {
            return [];
        }

        $result = [];

        foreach ($stations as $station) {
            $response = $responses[$station->slug] ?? null;
            $raw = null;

            if ($response instanceof \Illuminate\Http\Client\Response && $response->successful()) {
                $raw = $this->extractTitle($response->body(), $station->now_playing_mount);
            }

            $result[$station->slug] = $this->split($raw);
        }

        return $result;
    }

    protected function extractTitle(string $body, string $mount): ?string
    {
        $data = json_decode(trim($body), true);
        if ($data === null) {
            // Κάποια mount στέλνουν μη έγκυρα escapes (π.χ. «Now Playing\\: …»): τα αφαιρούμε και ξαναδοκιμάζουμε
            $data = json_decode(preg_replace('/\\\\(?!["\\\\\/bfnrtu])/', '', trim($body)), true);
        }
        $entry = $data['icestats'][$mount] ?? (is_array($data['icestats'] ?? null) ? reset($data['icestats']) : null);
        $title = is_array($entry) ? ($entry['title'] ?? null) : null;

        return is_string($title) ? self::fixEncoding($title) : null;
    }

    /**
     * Το live24 στέλνει UTF-8 που έχει κωδικοποιηθεί δεύτερη φορά ως Latin-1
     * («ÎÎÎÎ¡ÎÎ» αντί για «ΑΝΔΡΙ»). Το αναιρούμε μόνο όταν χρειάζεται.
     */
    public static function fixEncoding(string $text): string
    {
        $text = trim($text);

        if ($text === '' || preg_match('/\p{Greek}/u', $text)) {
            return $text;
        }

        $decoded = mb_convert_encoding($text, 'ISO-8859-1', 'UTF-8');

        return mb_check_encoding($decoded, 'UTF-8') ? trim($decoded) : $text;
    }

    /**
     * @return array{artist: ?string, title: ?string, raw: ?string}
     */
    public function split(?string $raw): array
    {
        if ($raw === null || $raw === '') {
            return ['artist' => null, 'title' => null, 'raw' => null];
        }

        $raw = trim(preg_replace('/^(now\s+playing|τώρα\s+παίζει)\s*[:\-]\s*/iu', '', $raw));
        if ($raw === '') {
            return ['artist' => null, 'title' => null, 'raw' => null];
        }
        $parts = preg_split('/\s+-\s+/u', $raw, 2);

        return count($parts) === 2
            ? ['artist' => self::tidy($parts[0]), 'title' => self::tidy($parts[1]), 'raw' => $raw]
            : ['artist' => null, 'title' => self::tidy($raw), 'raw' => $raw];
    }

    /** «ΑΝΔΡΙΑΝΑ ΜΠΑΜΠΑΛΗ» → «Ανδριανα Μπαμπαλη» θα έχανε τους τόνους, οπότε κρατάμε κεφαλαία όπως έρχονται. */
    protected static function tidy(string $s): string
    {
        return trim(preg_replace('/\s+/u', ' ', $s));
    }
}
