<?php

namespace App\Http\Controllers;

use App\Models\HomeSection;
use App\Models\Package;
use App\Models\Slide;
use App\Models\Station;
use App\Services\NewsFeed;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(Request $request, NewsFeed $news): View
    {
        $sections = HomeSection::query()->showing()->get()
            ->filter(fn (HomeSection $s) => view()->exists('home.sections.'.$s->type))
            ->values();
        $types = $sections->pluck('type')->unique();

        // Φορτώνουμε μόνο ό,τι χρειάζονται οι ενότητες που φαίνονται
        $data = [
            'slides' => $types->contains('slider') ? Slide::query()->showing()->get() : collect(),
            'stations' => $types->intersect(['dial', 'stations'])->isNotEmpty() ? Station::query()->active()->get() : collect(),
            'news' => $types->contains('news') ? $news->latest(10) : [],
            'packages' => $this->packagesFor($sections->whereIn('type', ['packages', 'offers'])),
            'canSeePrices' => $this->canSeePrices($request),
        ];

        // Ενότητα χωρίς περιεχόμενο (π.χ. καμία ενεργή προσφορά) δεν εμφανίζεται καθόλου
        $sections = $sections->reject(fn (HomeSection $s) => match ($s->type) {
            'slider' => $data['slides']->isEmpty(),
            'dial', 'stations' => $data['stations']->isEmpty(),
            'news' => $data['news'] === [],
            'packages', 'offers' => ($data['packages'][$s->id] ?? collect())->isEmpty(),
            default => false,
        })->values();

        return view('home', ['sections' => $sections, ...$data]);
    }

    /** @return array<int, Collection<int, Package>> */
    protected function packagesFor(Collection $sections): array
    {
        $out = [];

        foreach ($sections as $section) {
            $q = Package::query()->available();
            match ($section->setting('source', $section->type === 'offers' ? 'offers' : 'all')) {
                'featured' => $q->where('is_featured', true),
                'offers' => $q->where('is_offer', true),
                'chosen' => $q->whereIn('id', (array) $section->setting('package_ids', [])),
                default => null,
            };
            $out[$section->id] = $q->limit((int) $section->setting('limit', $section->type === 'offers' ? 12 : 6))->get();
        }

        return $out;
    }

    /** Τιμές βλέπουν μόνο επαληθευμένοι επαγγελματίες (και το προσωπικό) */
    protected function canSeePrices(Request $request): bool
    {
        return (bool) $request->user()?->canSeePrices();
    }
}
