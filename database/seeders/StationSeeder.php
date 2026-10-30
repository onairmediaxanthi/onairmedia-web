<?php

namespace Database\Seeders;

use App\Models\Station;
use Illuminate\Database\Seeder;

/**
 * Οι 12 σταθμοί όπως ήταν στο παλιό onairmedia.gr (js/main.js).
 * Τρέχει με ασφάλεια ξανά: ενημερώνει μόνο σταθμούς που δεν έχουν αλλαχτεί από τη διαχείριση.
 */
class StationSeeder extends Seeder
{
    public function run(): void
    {
        $stations = [
            ['star-888', 'Star 88.8', 88.8, 'Το ραδιόφωνο όπως το θέλουμε', 'Ποπ & επιτυχίες', 'https://onairmediagroup.live24.gr/star888xanthi', 'Star.webp', '#f59e0b'],
            ['radio-dj-946', 'Radio DJ 94.6', 94.6, 'Παίζει μόνο επιτυχίες, παίζει για σένα!', 'Dance & επιτυχίες', 'https://onairmediagroup.live24.gr/radiodeejay946xanthi', 'radiodj-full.webp', '#8b5cf6'],
            ['dream-969', 'Dream 96.9', 96.9, 'Άκου δυνατά', 'Ελληνική μουσική', 'https://netradio.live24.gr/topfm969xanthi', 'Dream.webp', '#ec4899'],
            ['metro-978', 'Metro 97.8 FM', 97.8, 'Παίζει μόνο τα καλύτερα', 'Ποπ', 'https://onairmediagroup.live24.gr/metrofm978xanthi', 'Metrofm.webp', '#06b6d4'],
            ['velvet-984', 'Velvet 98.4 FM', 98.4, 'Οι μεγαλύτερες επιτυχίες του χτες και του σήμερα', 'Επιτυχίες όλων των εποχών', 'https://onairmediagroup.live24.gr/velvet984xanthi', 'velvet984.webp', '#be123c'],
            ['kral-fm-100', 'Kral FM 100', 100.0, 'Zirvenin Tek sahibi', 'Τουρκική μουσική', 'https://onairmediagroup.live24.gr/kralfm100xanthi', 'kral.webp', '#dc2626'],
            ['glenti-1011', 'Γλέντι 101.1 FM', 101.1, 'Μόνο λαϊκά', 'Λαϊκή μουσική', 'https://onairmediagroup.live24.gr/glenti1011xanthi', 'Glentifm.webp', '#ea580c'],
            ['legend-1016', 'Legend 101.6 FM', 101.6, 'Είναι μόνο Rock', 'Rock', 'https://onairmediagroup.live24.gr/legend1016xanthi', 'Legend1016.webp', '#52525b'],
            ['erotikos-103', 'Ερωτικός 103', 103.0, 'Όλη η Ξάνθη ερωτεύεται εδώ!', 'Ελληνική μπαλάντα', 'https://onairmediagroup.live24.gr/erotikosxanthi', 'erotikos103.webp', '#e11d48'],
            ['goal-1051', 'Goal 105.1 FM', 105.1, 'Τώρα η Ξάνθη έχει το δικό της αθλητικό ραδιόφωνο!', 'Αθλητικό', 'https://onairmediagroup.live24.gr/goalfm1051xanthi', 'goalfm.webp', '#16a34a'],
            ['sugar-1055', 'Sugar 105.5', 105.5, 'Όλες οι γλυκές επιτυχίες είναι εδώ!', 'Ποπ επιτυχίες', 'https://onairmediagroup.live24.gr/virus1055xanthi', 'Sugar.webp', '#d946ef'],
            ['rythmos-106', 'Ρυθμός 106 FM', 106.0, 'Η καλύτερη ελληνική μουσική στην πόλη', 'Ελληνική μουσική', 'https://onairmediagroup.live24.gr/rythmos106xanthi', 'RYTHMOS.webp', '#2563eb'],
        ];

        foreach ($stations as $i => [$slug, $name, $freq, $slogan, $genre, $stream, $logo, $color]) {
            $station = Station::firstOrNew(['slug' => $slug]);

            // Δεν πατάμε ό,τι έχει αλλαχτεί ήδη από τη διαχείριση
            if ($station->exists && $station->updated_at?->gt($station->created_at)) {
                continue;
            }

            $station->fill([
                'name' => $name,
                'frequency' => $freq,
                'slogan' => $slogan,
                'genre' => $genre,
                'coverage' => 'Ξάνθη',
                'stream_url' => $stream,
                'now_playing_mount' => '/'.basename(parse_url($stream, PHP_URL_PATH)),
                'logo_path' => 'stations/'.$logo,
                'color' => $color,
                'sort' => ($i + 1) * 10,
                'is_active' => true,
            ])->save();
        }
    }
}
