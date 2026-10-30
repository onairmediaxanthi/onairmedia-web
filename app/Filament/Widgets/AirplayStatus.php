<?php

namespace App\Filament\Widgets;

use App\Models\Station;
use App\Services\Airplay\AirplayPruner;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\DB;

/** Κατάσταση καταγραφής ανά σταθμό + χώρος που πιάνει το ιστορικό */
class AirplayStatus extends Widget
{
    protected string $view = 'filament.airplay.status';

    protected int|string|array $columnSpan = 'full';

    protected static bool $isDiscovered = false; // μόνο στη σελίδα «Τι παίζουν», όχι στον πίνακα ελέγχου

    protected static bool $isLazy = false;

    protected ?string $pollingInterval = '60s';

    protected function getViewData(): array
    {
        $today = DB::table('airplay_daily')->where('day', today()->toDateString())->groupBy('station_id')->pluck(DB::raw('SUM(plays)'), 'station_id');

        return [
            'stations' => Station::query()->active()->orderBy('sort')->get(),
            'today' => $today,
            'usage' => AirplayPruner::usage(),
            'keep' => (int) \App\Models\Setting::get('airplay_keep_days', 90),
            'enabled' => (bool) \App\Models\Setting::get('airplay_enabled', true),
        ];
    }
}
