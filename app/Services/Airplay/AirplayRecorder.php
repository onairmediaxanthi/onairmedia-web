<?php

namespace App\Services\Airplay;

use App\Models\Airplay;
use App\Models\Setting;
use App\Models\Song;
use App\Models\Station;
use App\Services\NowPlaying;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Normalizer;

/**
 * Κάθε λεπτό: διαβάζει «τι παίζει» σε όλους τους σταθμούς και γράφει κάθε νέο τραγούδι.
 * Jingles, διαφημίσεις και ονόματα σταθμών αγνοούνται (λέξεις στις ρυθμίσεις).
 */
class AirplayRecorder
{
    public function __construct(protected NowPlaying $nowPlaying) {}

    /** @return array{recorded: int, skipped: int} */
    public function record(?Carbon $now = null): array
    {
        $now ??= now();
        if (! Setting::get('airplay_enabled', true)) {
            return ['recorded' => 0, 'skipped' => 0];
        }
        $playing = $this->nowPlaying->fresh();
        $stations = Station::query()->active()->where('airplay_enabled', true)->whereNotNull('now_playing_mount')->get();
        $recorded = $skipped = 0;

        foreach ($stations as $st) {
            $np = $playing[$st->slug] ?? null;
            $raw = $np['raw'] ?? null;
            if (! $raw) {
                continue;
            }
            $changed = self::fold($raw) !== self::fold((string) $st->last_title);
            $st->forceFill(['last_seen_at' => $now, 'last_title' => mb_substr($raw, 0, 400)]);
            if (! $changed) {
                $st->save();

                continue;
            }
            if ($this->ignored($np, $st)) {
                $skipped++;
                $st->save();

                continue;
            }

            $title = mb_substr((string) $np['title'], 0, 200);
            $artist = $np['artist'] ? mb_substr($np['artist'], 0, 200) : null;
            $song = Song::query()->firstOrCreate(['norm' => Song::normalize($artist, $title)], ['artist' => $artist, 'title' => $title, 'created_at' => $now]);
            if ($song->ignored) {
                $skipped++;
                $st->save();

                continue;
            }

            DB::transaction(function () use ($st, $song, $now) {
                Airplay::query()->insert(['station_id' => $st->id, 'song_id' => $song->id, 'played_at' => $now]);
                DB::table('airplay_daily')->upsert(
                    [['station_id' => $st->id, 'song_id' => $song->id, 'day' => $now->toDateString(), 'plays' => 1]],
                    ['station_id', 'day', 'song_id'],
                    ['plays' => DB::raw('plays + 1')],
                );
            });
            $st->forceFill(['last_airplay_at' => $now])->save();
            $recorded++;
        }

        return ['recorded' => $recorded, 'skipped' => $skipped];
    }

    /** Δεν είναι τραγούδι: λέξεις-κλειδιά, όνομα σταθμού, ή (προαιρετικά) χωρίς «καλλιτέχνης - τίτλος» */
    public function ignored(array $np, Station $st): bool
    {
        $raw = self::fold((string) $np['raw']);
        if (mb_strlen($raw) < 3) {
            return true;
        }
        if (Setting::get('airplay_require_artist', true) && blank($np['artist'])) {
            return true;
        }
        $name = self::fold($st->name);
        if ($raw === $name || ($st->frequency && str_contains($raw, (string) $st->frequency)) || str_contains($raw, 'http') || str_contains($raw, 'www')) {
            return true;
        }
        foreach ((array) Setting::get('airplay_ignore', []) as $word) {
            $w = self::fold((string) $word);
            if ($w !== '' && preg_match('/(^|[^\p{L}\p{N}])'.preg_quote($w, '/').'($|[^\p{L}\p{N}])/u', $raw)) {
                return true;
            }
        }

        return false;
    }

    /** πεζά, χωρίς τόνους, ένα κενό */
    public static function fold(string $s): string
    {
        $s = mb_strtolower(trim($s));
        if (class_exists(Normalizer::class)) {
            $s = preg_replace('/\p{Mn}/u', '', Normalizer::normalize($s, Normalizer::FORM_D));
        }

        return preg_replace('/\s+/u', ' ', $s);
    }
}
