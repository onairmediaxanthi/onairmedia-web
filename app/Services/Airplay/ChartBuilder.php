<?php

namespace App\Services\Airplay;

use App\Models\Chart;
use App\Models\Setting;
use App\Models\Song;
use App\Models\Station;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Top 40 ανά σταθμό.
 * - Εβδομάδα (Δευτέρα–Κυριακή): για τη σελίδα του σταθμού, σβήνεται μόνη της μετά από λίγες εβδομάδες.
 * - Μήνας: το μόνιμο αρχείο, βγαίνει την 1η κάθε μήνα και σβήνεται μόνο από τη διαχείριση.
 * - Έτος: δεν αποθηκεύεται· υπολογίζεται από τα Top 40 των μηνών του.
 */
class ChartBuilder
{
    public static function size(): int
    {
        return max(5, min(100, (int) Setting::get('chart_size', 40)));
    }

    public static function start(string $period, Carbon $date): Carbon
    {
        return $period === 'month' ? $date->copy()->startOfMonth()->startOfDay() : $date->copy()->startOfWeek(Carbon::MONDAY)->startOfDay();
    }

    public static function end(string $period, Carbon $start): Carbon
    {
        return $period === 'month' ? $start->copy()->endOfMonth()->startOfDay() : $start->copy()->addDays(6);
    }

    /** Η περίοδος που μόλις έκλεισε */
    public static function previous(string $period, ?Carbon $now = null): Carbon
    {
        $now ??= now();

        return $period === 'month' ? $now->copy()->startOfMonth()->subMonthNoOverflow()->startOfDay() : $now->copy()->startOfWeek(Carbon::MONDAY)->subWeek()->startOfDay();
    }

    /**
     * Κατάταξη από την ημερήσια σύνοψη, χωρίς αποθήκευση (και για τη «ζωντανή» εικόνα της τρέχουσας περιόδου).
     *
     * @return Collection<int, object{song_id:int, plays:int}>
     */
    public static function ranking(Station $station, Carbon $from, Carbon $to, ?int $limit = null): Collection
    {
        return DB::table('airplay_daily')
            ->join('songs', 'songs.id', '=', 'airplay_daily.song_id')
            ->where('airplay_daily.station_id', $station->id)
            ->whereBetween('airplay_daily.day', [$from->toDateString(), $to->toDateString()])
            ->where('songs.ignored', false)
            ->groupBy('airplay_daily.song_id')
            ->havingRaw('SUM(airplay_daily.plays) >= ?', [max(1, (int) Setting::get('chart_min_plays', 2))])
            ->orderByRaw('SUM(airplay_daily.plays) DESC')
            ->orderByRaw('MAX(airplay_daily.day) DESC')
            ->orderBy('airplay_daily.song_id')
            ->limit($limit ?? self::size())
            ->get([DB::raw('airplay_daily.song_id as song_id'), DB::raw('SUM(airplay_daily.plays) as plays')]);
    }

    /** Δημιουργεί (ή ξαναφτιάχνει) τη λίστα μιας περιόδου. @return int πλήθος θέσεων */
    public function build(Station $station, string $period, Carbon $date): int
    {
        $start = self::start($period, $date);
        $rows = self::ranking($station, $start, self::end($period, $start));
        $prevStart = $period === 'month' ? $start->copy()->subMonthNoOverflow() : $start->copy()->subWeek();
        $prev = Chart::query()->where('station_id', $station->id)->where('period', $period)->whereDate('period_start', $prevStart->toDateString())->get()->keyBy('song_id');

        DB::transaction(function () use ($station, $period, $start, $rows, $prev) {
            Chart::query()->where('station_id', $station->id)->where('period', $period)->whereDate('period_start', $start->toDateString())->delete();
            $insert = [];
            foreach ($rows->values() as $i => $r) {
                $pos = $i + 1;
                $p = $prev->get($r->song_id);
                $insert[] = [
                    'station_id' => $station->id, 'period' => $period, 'period_start' => $start->toDateString(), 'position' => $pos, 'song_id' => $r->song_id,
                    'plays' => (int) $r->plays, 'prev_position' => $p?->position, 'peak' => $p ? min($p->peak, $pos) : $pos,
                    'periods_on' => $p ? $p->periods_on + 1 : 1,
                ];
            }
            if ($insert) {
                Chart::query()->insert($insert);
            }
        });

        return $rows->count();
    }

    /** Όλοι οι σταθμοί με καταγραφή. @return array<string, int> */
    public function buildAll(string $period = 'week', ?Carbon $date = null): array
    {
        $date ??= self::previous($period);
        $out = [];
        foreach (Station::query()->where('airplay_enabled', true)->orderBy('sort')->get() as $st) {
            $out[$st->name] = $this->build($st, $period, $date);
        }

        return $out;
    }

    public static function latest(Station $station, string $period): ?Carbon
    {
        $d = Chart::query()->where('station_id', $station->id)->where('period', $period)->max('period_start');

        return $d ? Carbon::parse($d) : null;
    }

    /** @return list<string> ημερομηνίες έναρξης (Y-m-d), νεότερη πρώτη */
    public static function available(Station $station, string $period): array
    {
        return Chart::query()->where('station_id', $station->id)->where('period', $period)->distinct()->orderByDesc('period_start')
            ->pluck('period_start')->map(fn ($d) => Carbon::parse($d)->toDateString())->all();
    }

    /** @return list<int> έτη με μηνιαίο Top 40 */
    public static function years(Station $station): array
    {
        return collect(self::available($station, 'month'))->map(fn ($d) => (int) substr($d, 0, 4))->unique()->values()->all();
    }

    /**
     * Top 40 της χρονιάς, από τα μηνιαία Top 40: σύνολο μεταδόσεων, σε πόσους μήνες μπήκε, καλύτερη θέση.
     *
     * @return Collection<int, object{position:int, song: ?Song, plays:int, months:int, best:int}>
     */
    public static function year(Station $station, int $year): Collection
    {
        $rows = DB::table('charts')->where('station_id', $station->id)->where('period', 'month')
            ->whereBetween('period_start', [$year.'-01-01', $year.'-12-31'])
            ->groupBy('song_id')
            ->orderByRaw('SUM(plays) DESC')->orderByRaw('MIN(position)')
            ->limit(self::size())
            ->get(['song_id', DB::raw('SUM(plays) as plays'), DB::raw('COUNT(*) as months'), DB::raw('MIN(position) as best')]);
        $songs = Song::query()->whereIn('id', $rows->pluck('song_id'))->get()->keyBy('id');

        return $rows->values()->map(fn ($r, $i) => (object) ['position' => $i + 1, 'song' => $songs[$r->song_id] ?? null, 'plays' => (int) $r->plays,
            'months' => (int) $r->months, 'best' => (int) $r->best]);
    }

    /**
     * Αναζήτηση τραγουδιού στο αρχείο: σε ποιους μήνες και σε ποια θέση μπήκε.
     *
     * @return Collection<int, Chart>
     */
    public static function search(string $q, ?int $stationId = null): Collection
    {
        $q = trim($q);
        if (mb_strlen($q) < 2) {
            return collect();
        }

        return Chart::query()->with(['song', 'station'])->where('period', 'month')
            ->when($stationId, fn ($x) => $x->where('station_id', $stationId))
            ->whereHas('song', fn ($s) => $s->where('title', 'like', "%{$q}%")->orWhere('artist', 'like', "%{$q}%"))
            ->orderByDesc('period_start')->orderBy('position')->limit(200)->get();
    }
}
