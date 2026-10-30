<?php

namespace App\Services\Airplay;

use App\Models\Setting;
use Illuminate\Support\Facades\DB;

/**
 * Κρατά τη βάση μικρή. Μόνιμο είναι μόνο το μηνιαίο Top 40· όλα τα υπόλοιπα σβήνονται αυτόματα,
 * και τα τραγούδια που δεν χρησιμοποιούνται πουθενά φεύγουν κι αυτά.
 */
class AirplayPruner
{
    /** Εβδομαδιαίες λίστες: λίγες εβδομάδες, μόνο για τη σελίδα του σταθμού */
    public const WEEKS_KEPT = 8;

    /**
     * Αυτόματος καθαρισμός (κάθε βράδυ):
     * - μεταδόσεις παλαιότερες από όσες ημέρες ορίζει η ρύθμιση,
     * - ημερήσια σύνοψη πριν από τον προηγούμενο μήνα (το μηνιαίο Top 40 του έχει ήδη βγει),
     * - εβδομαδιαίες λίστες παλαιότερες από 8 εβδομάδες.
     * Τα μηνιαία Top 40 δεν σβήνονται ποτέ αυτόματα.
     *
     * @return array<string, int>
     */
    public function prune(): array
    {
        $out = [
            'airplays' => $this->chunkDelete('airplays', 'played_at', now()->subDays(max(1, (int) Setting::get('airplay_keep_days', 30)))->startOfDay()),
            'daily' => $this->chunkDelete('airplay_daily', 'day', now()->startOfMonth()->subMonthNoOverflow()->toDateString()),
            'weeks' => DB::table('charts')->where('period', 'week')->where('period_start', '<', now()->subWeeks(self::WEEKS_KEPT)->toDateString())->delete(),
        ];
        $out['songs'] = $this->orphanSongs();

        return $out;
    }

    /** Διαγραφή μεταδόσεων από τη διαχείριση (δεν αγγίζει το αρχείο Top 40). $stationId null = όλοι */
    public function wipe(?int $stationId, ?int $olderThanDays): array
    {
        $scope = fn ($q) => $stationId ? $q->where('station_id', $stationId) : $q;
        $out = [];
        $q = $scope(DB::table('airplays'));
        $out['airplays'] = ($olderThanDays ? $q->where('played_at', '<', now()->subDays($olderThanDays)->startOfDay()) : $q)->delete();
        $q = $scope(DB::table('airplay_daily'));
        $out['daily'] = ($olderThanDays ? $q->where('day', '<', now()->subDays($olderThanDays)->toDateString()) : $q)->delete();
        if (! $olderThanDays) {
            DB::table('stations')->when($stationId, fn ($q) => $q->where('id', $stationId))->update(['last_title' => null]);
        }
        $out['songs'] = $this->orphanSongs();

        return $out;
    }

    /** Διαγραφή Top 40 από το αρχείο: μηνιαία (και εβδομαδιαία) έως και τον μήνα $until. $stationId null = όλοι */
    public function wipeCharts(?int $stationId, string $until): array
    {
        $before = \Illuminate\Support\Carbon::parse($until)->endOfMonth()->toDateString();
        $n = DB::table('charts')->when($stationId, fn ($q) => $q->where('station_id', $stationId))->where('period_start', '<=', $before)->delete();

        return ['charts' => $n, 'songs' => $this->orphanSongs()];
    }

    protected function chunkDelete(string $table, string $column, $before): int
    {
        $total = 0;
        do {
            $ids = DB::table($table)->where($column, '<', $before)->limit(5000)->pluck($table === 'airplays' ? 'id' : $column);
            if ($table === 'airplays') {
                $n = $ids->isEmpty() ? 0 : DB::table($table)->whereIn('id', $ids)->delete();
            } else {
                $n = DB::table($table)->where($column, '<', $before)->delete(); // λίγες γραμμές, μια φορά
            }
            $total += $n;
        } while ($table === 'airplays' && $n > 0);

        return $total;
    }

    protected function orphanSongs(): int
    {
        return DB::table('songs')
            ->where('ignored', false) // οι «εξαιρέσεις» μένουν, για να μη ξαναμπαίνουν
            ->whereNotExists(fn ($q) => $q->from('airplays')->whereColumn('airplays.song_id', 'songs.id'))
            ->whereNotExists(fn ($q) => $q->from('airplay_daily')->whereColumn('airplay_daily.song_id', 'songs.id'))
            ->whereNotExists(fn ($q) => $q->from('charts')->whereColumn('charts.song_id', 'songs.id'))
            ->delete();
    }

    /** Εκτίμηση χώρου (MB) και πλήθος γραμμών, για τη διαχείριση */
    public static function usage(): array
    {
        $tables = ['airplays', 'airplay_daily', 'charts', 'songs'];
        $rows = [];
        foreach ($tables as $t) {
            $rows[$t] = DB::table($t)->count();
        }
        $bytes = null;
        if (DB::getDriverName() === 'mysql') {
            $bytes = (int) DB::table('information_schema.TABLES')->where('TABLE_SCHEMA', DB::getDatabaseName())->whereIn('TABLE_NAME', $tables)
                ->sum(DB::raw('DATA_LENGTH + INDEX_LENGTH'));
        }
        // Χωρίς information_schema: ~70 bytes ανά μετάδοση με τα ευρετήρια, ~40 ανά γραμμή σύνοψης, ~250 ανά τραγούδι
        $bytes ??= $rows['airplays'] * 70 + $rows['airplay_daily'] * 40 + $rows['charts'] * 40 + $rows['songs'] * 250;

        return ['rows' => $rows, 'mb' => round($bytes / 1048576, 1)];
    }
}
