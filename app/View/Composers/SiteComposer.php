<?php

namespace App\View\Composers;

use App\Models\Station;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

/** Δεδομένα που χρειάζεται ο player σε κάθε σελίδα */
class SiteComposer
{
    public function compose(View $view): void
    {
        $view->with('playerStations', Cache::remember('player:stations', 300, fn () => Station::query()->active()->get()
            ->map(fn (Station $s) => [
                'slug' => $s->slug,
                'name' => $s->name,
                'freq' => $s->frequency_label,
                'f' => (float) $s->frequency,
                'stream' => $s->stream_url,
                'logo' => $s->logo_url,
                'color' => $s->color,
                'url' => route('stations.show', $s),
            ])->values()->all()));
    }
}
