<?php

namespace App\View\Composers;

use App\Models\HomeSection;
use App\Models\Package;
use App\Models\Station;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

/** Δεδομένα που χρειάζεται ο player σε κάθε σελίδα */
class SiteComposer
{
    public function compose(View $view): void
    {
        $view->with('menu', Cache::remember('site:menu', 300, fn () => HomeSection::query()->showing()
            ->whereNotNull('anchor')->get()
            ->filter(fn (HomeSection $s) => filled($s->setting('menu_label')))
            // Χωρίς ενεργά πακέτα, η ενότητα δεν φαίνεται: ούτε και στο μενού
            ->reject(fn (HomeSection $s) => $s->type === 'packages' && ! Package::query()->available()->exists())
            ->map(fn (HomeSection $s) => [$s->setting('menu_label'), route('home').'#'.$s->anchor])
            ->values()->all()));

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
