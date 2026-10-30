<?php

namespace App\Http\Controllers;

use App\Models\Station;
use App\Support\Audience;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class StationController extends Controller
{
    public function show(Request $request, Station $station): View
    {
        abort_unless($station->is_active, 404);

        return view('stations.show', [
            'station' => $station,
            // Το προφίλ γράφεται στη διαχείριση: καθαρίζουμε κάθε script/επικίνδυνο attribute
            'description' => $station->description ? Str::sanitizeHtml($station->description) : null,
            'audience' => Audience::labelsFor($station->audience),
            'canSeePrices' => (bool) $request->user()?->canSeePrices(),
            'book' => \App\Services\Quotes\PriceBook::for($request->user()),
            'others' => Station::query()->active()->whereKeyNot($station->getKey())->get(),
            ...$this->charts($station),
        ]);
    }

    /** Top 40 εβδομάδας και μήνα, και «Μόλις έπαιξαν» (με cache: η σελίδα δεν ρωτά τη βάση σε κάθε επίσκεψη) */
    protected function charts(Station $station): array
    {
        if (! $station->chart_public || ! $station->airplay_enabled) {
            return ['charts' => [], 'recent' => collect()];
        }
        $charts = [];
        foreach (['week', 'month'] as $period) {
            $start = \App\Services\Airplay\ChartBuilder::latest($station, $period);
            if ($start) {
                $charts[$period] = ['start' => $start, 'rows' => \Illuminate\Support\Facades\Cache::remember('chart:'.$station->id.':'.$period.':'.$start->toDateString(), 600,
                    fn () => \App\Models\Chart::query()->with('song')->where('station_id', $station->id)->where('period', $period)
                        ->whereDate('period_start', $start->toDateString())->orderBy('position')->get())];
            }
        }
        $recent = \Illuminate\Support\Facades\Cache::remember('recent:'.$station->id, 60, fn () => \App\Models\Airplay::query()
            ->with('song')->where('station_id', $station->id)->orderByDesc('played_at')->limit(8)->get());

        return ['charts' => $charts, 'recent' => $recent];
    }
}
