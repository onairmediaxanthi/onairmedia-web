<?php

namespace App\Livewire;

use App\Models\AdSlot;
use App\Models\Package;
use App\Models\Screen;
use App\Models\Setting;
use App\Models\Station;
use App\Services\CustomerEvents;
use App\Services\Quotes\QuoteCalculator;
use App\Services\Quotes\QuoteService;
use App\Services\Quotes\Recommender;
use App\Support\Audience;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Studio Καμπάνιας: οδηγός με ερωτήσεις, δική σας επιλογή ή έτοιμο πακέτο.
 * Τιμές βλέπουν και αποθηκεύουν μόνο επαληθευμένοι πελάτες. Οι επισκέπτες απαντούν
 * στις ερωτήσεις και, μετά την εγγραφή, βρίσκουν τις απαντήσεις τους εδώ.
 */
class QuoteBuilder extends Component
{
    public const SESSION_KEY = 'quote_wizard';

    #[Url(as: 'tropos', except: 'start')]
    public string $mode = 'start';

    public int $step = 1;

    public bool $showResults = false;

    // ---- Οδηγός ----
    public string $goal = 'awareness';

    public $budget = 500;

    public $days = 30;

    public ?string $startsOn = null;

    public array $ages = [];

    public array $profiles = [];

    public ?string $gender = null;

    public array $media = ['radio', 'news', 'led'];

    public string $material = 'ready';

    public $seconds = 30;

    /** Υπολογίζονται μόνο στον server· ο browser δεν μπορεί να τα αλλάξει */
    #[Locked]
    public array $proposals = [];

    #[Locked]
    public ?float $minBudget = null;

    // ---- Δική σας επιλογή ----
    public string $title = '';

    public array $radio = [];

    public array $banners = [];

    public array $screens = [];

    public $articles = 0;

    public bool $articleWriting = false;

    public array $creative = [];

    public ?int $highlightPackage = null;

    public ?string $error = null;

    public function mount(): void
    {
        $this->startsOn = today()->addDays(3)->format('Y-m-d');
        $this->resetManual();

        if ($slug = request()->query('stathmos')) {
            $st = Station::query()->active()->where('slug', $slug)->first();
            if ($st && isset($this->radio[$st->id])) {
                $this->radio[$st->id]['on'] = true;
                $this->mode = 'manual';
            }
        }
        if ($pkg = (int) request()->query('paketo')) {
            $this->highlightPackage = $pkg;
            $this->mode = 'packages';
        }

        // Επιστροφή μετά από εγγραφή/επιβεβαίωση: συνεχίζουμε από εκεί που έμεινε
        if ($this->canSeePrices() && ($saved = session()->pull(self::SESSION_KEY))) {
            foreach (['goal', 'budget', 'days', 'startsOn', 'ages', 'profiles', 'gender', 'media', 'material', 'seconds'] as $k) {
                if (array_key_exists($k, $saved)) {
                    $this->{$k} = $saved[$k];
                }
            }
            $this->mode = 'wizard';
            $this->step = 4;
            $this->results();
        }
    }

    protected function resetManual(): void
    {
        $this->radio = [];
        foreach ($this->sellableStations() as $s) {
            $this->radio[$s->id] = ['on' => false, 'spots' => 4, 'seconds' => (int) ($s->spot_seconds ?: 30), 'position' => 'standard',
                'fixed' => false, 'ml' => 0, 'mr' => 0];
        }
        $this->banners = AdSlot::query()->active()->whereNotNull('price_per_day')->pluck('id')
            ->mapWithKeys(fn ($id) => [$id => ['on' => false, 'days' => null]])->all();
        $this->screens = Screen::query()->active()->whereNotNull('price_per_day')->pluck('id')
            ->mapWithKeys(fn ($id) => [$id => ['on' => false, 'days' => null]])->all();
        $this->creative = array_fill_keys(array_keys(QuoteCalculator::CREATIVE), false);
    }

    public function canSeePrices(): bool
    {
        return (bool) Auth::user()?->canSeePrices();
    }

    public function canSave(): bool
    {
        $u = Auth::user();

        return $u !== null && $u->isCustomer() && $u->hasVerifiedEmail();
    }

    protected function sellableStations()
    {
        return Station::query()->active()->get()->filter(fn ($s) => QuoteCalculator::stationSellable($s))->values();
    }

    public function choose(string $mode): void
    {
        $this->mode = in_array($mode, ['start', 'wizard', 'manual', 'packages'], true) ? $mode : 'start';
        $this->error = null;
        if ($this->mode === 'wizard') {
            $this->step = 1;
            $this->showResults = false;
        }
    }

    // ---------------- Οδηγός ----------------

    protected function stepRules(int $step): array
    {
        return match ($step) {
            1 => ['goal' => 'required|in:'.implode(',', array_keys(Recommender::GOALS))],
            2 => [
                'budget' => 'required|numeric|min:50|max:200000',
                'days' => 'required|integer|min:1|max:'.QuoteCalculator::MAX_DAYS,
                'startsOn' => 'nullable|date_format:Y-m-d|after_or_equal:today',
            ],
            3 => [
                'ages' => 'array', 'ages.*' => 'in:'.implode(',', array_keys(Audience::GROUPS['ages']['options'])),
                'profiles' => 'array', 'profiles.*' => 'in:'.implode(',', array_keys(Audience::GROUPS['profiles']['options'])),
                'gender' => 'nullable|in:'.implode(',', array_keys(Audience::GENDER)),
            ],
            4 => [
                'media' => 'required|array|min:1', 'media.*' => 'in:radio,news,led',
                'material' => 'required|in:ready,need',
                'seconds' => 'required|integer|in:15,20,30,45,60',
            ],
            default => [],
        };
    }

    protected function messages(): array
    {
        return [
            'budget.min' => 'Το ελάχιστο ποσό είναι 50 €.',
            'budget.required' => 'Γράψτε περίπου πόσα θέλετε να διαθέσετε.',
            'days.min' => 'Τουλάχιστον μία ημέρα.',
            'startsOn.after_or_equal' => 'Η έναρξη δεν μπορεί να είναι στο παρελθόν.',
            'media.required' => 'Διαλέξτε τουλάχιστον ένα μέσο.',
            'media.min' => 'Διαλέξτε τουλάχιστον ένα μέσο.',
        ];
    }

    public function next(): void
    {
        $this->validate($this->stepRules($this->step));
        if ($this->step < 4) {
            $this->step++;

            return;
        }
        $this->results();
    }

    public function back(): void
    {
        if ($this->showResults) {
            $this->showResults = false;

            return;
        }
        $this->step > 1 ? $this->step-- : $this->choose('start');
    }

    public function goToStep(int $step): void
    {
        $this->showResults = false;
        $this->step = max(1, min(4, $step));
    }

    public function results()
    {
        foreach ([1, 2, 3, 4] as $s) {
            $this->validate($this->stepRules($s));
        }

        if (! $this->canSeePrices()) {
            // Κρατάμε τις απαντήσεις και τον στέλνουμε να γραφτεί/επιβεβαιώσει
            session()->put(self::SESSION_KEY, $this->answers());
            session()->put('url.intended', route('quote.builder'));

            return $this->redirectRoute(Auth::check() ? 'verification.notice' : 'register');
        }

        $out = (new Recommender)->recommend($this->answers());
        $this->proposals = $out['proposals'];
        $this->minBudget = $out['min_budget'];
        $this->showResults = true;

        if (Auth::user()?->isCustomer() && ! session()->has('wizard_logged')) {
            session()->put('wizard_logged', true);
            CustomerEvents::record('wizard_used', Auth::user(), null, ['budget' => (float) $this->budget, 'days' => (int) $this->days, 'goal' => $this->goal]);
        }
    }

    protected function answers(): array
    {
        return [
            'goal' => $this->goal, 'budget' => (float) $this->budget, 'days' => (int) $this->days, 'startsOn' => $this->startsOn,
            'ages' => array_values($this->ages), 'profiles' => array_values($this->profiles), 'gender' => $this->gender,
            'media' => array_values($this->media), 'material' => $this->material, 'seconds' => (int) $this->seconds,
        ];
    }

    public function saveProposal(int $index)
    {
        $p = $this->proposals[$index] ?? null;
        if (! $p) {
            return null;
        }
        $title = (Recommender::GOALS[$this->goal]['label'] ?? 'Καμπάνια').' · '.$p['title'];

        return $this->store($title, 'wizard', $p['specs'], ['wizard' => $this->answers(), 'strategy' => $p['key']]);
    }

    /** Παίρνει μια πρόταση και την ανοίγει στη «δική σας επιλογή» για αλλαγές */
    public function customize(int $index): void
    {
        $p = $this->proposals[$index] ?? null;
        if (! $p) {
            return;
        }
        $this->resetManual();
        foreach ($p['specs'] as $spec) {
            match ($spec['type']) {
                'radio' => isset($this->radio[$spec['station_id']]) ? $this->radio[$spec['station_id']] = [
                    'on' => true, 'spots' => $spec['spots_per_day'], 'seconds' => $spec['seconds'], 'position' => $spec['position'],
                    'fixed' => (bool) $spec['fixed_hour'], 'ml' => $spec['mentions_live'] ?? 0, 'mr' => $spec['mentions_recorded'] ?? 0] : null,
                'banner' => isset($this->banners[$spec['slot_id']]) ? $this->banners[$spec['slot_id']] = ['on' => true, 'days' => $spec['days'] != $this->days ? $spec['days'] : null] : null,
                'led' => isset($this->screens[$spec['screen_id']]) ? $this->screens[$spec['screen_id']] = ['on' => true, 'days' => $spec['days'] != $this->days ? $spec['days'] : null] : null,
                'creative' => $this->creative[$spec['key']] = true,
                default => null,
            };
        }
        $this->title = (Recommender::GOALS[$this->goal]['label'] ?? 'Καμπάνια').' · '.$p['title'];
        $this->mode = 'manual';
    }

    // ---------------- Δική σας επιλογή ----------------

    protected function manualSpecs(): array
    {
        $days = max(1, min(QuoteCalculator::MAX_DAYS, (int) $this->days));
        $specs = [];
        foreach ($this->radio as $id => $r) {
            if (! empty($r['on'])) {
                $specs[] = ['type' => 'radio', 'station_id' => (int) $id, 'spots_per_day' => (int) $r['spots'], 'seconds' => (int) $r['seconds'],
                    'position' => (string) $r['position'], 'fixed_hour' => (bool) $r['fixed'], 'mentions_live' => (int) $r['ml'],
                    'mentions_recorded' => (int) $r['mr'], 'days' => $days];
            }
        }
        foreach ($this->banners as $id => $b) {
            if (! empty($b['on'])) {
                $specs[] = ['type' => 'banner', 'slot_id' => (int) $id, 'days' => (int) ($b['days'] ?: $days)];
            }
        }
        foreach ($this->screens as $id => $s) {
            if (! empty($s['on'])) {
                $specs[] = ['type' => 'led', 'screen_id' => (int) $id, 'days' => (int) ($s['days'] ?: $days)];
            }
        }
        if ((int) $this->articles > 0) {
            $specs[] = ['type' => 'article', 'count' => (int) $this->articles, 'writing' => $this->articleWriting];
        }
        foreach ($this->creative as $key => $on) {
            if ($on) {
                $specs[] = ['type' => 'creative', 'key' => $key];
            }
        }

        return $specs;
    }

    public function saveManual()
    {
        $this->validate([
            'days' => 'required|integer|min:1|max:'.QuoteCalculator::MAX_DAYS,
            'startsOn' => 'nullable|date_format:Y-m-d|after_or_equal:today',
            'title' => 'nullable|string|max:120',
            'radio.*.spots' => 'integer|min:0|max:'.QuoteCalculator::MAX_SPOTS_PER_DAY,
            'radio.*.seconds' => 'integer|min:5|max:120',
            'radio.*.ml' => 'integer|min:0|max:24',
            'radio.*.mr' => 'integer|min:0|max:24',
            'radio.*.position' => 'in:'.implode(',', array_keys(QuoteCalculator::POSITIONS)),
            'banners.*.days' => 'nullable|integer|min:1|max:'.QuoteCalculator::MAX_DAYS,
            'screens.*.days' => 'nullable|integer|min:1|max:'.QuoteCalculator::MAX_DAYS,
            'articles' => 'integer|min:0|max:30',
        ], [], ['radio.*.spots' => 'μεταδόσεις', 'radio.*.seconds' => 'διάρκεια σποτ']);

        return $this->store($this->title ?: 'Η καμπάνια μου', 'manual', $this->manualSpecs());
    }

    public function savePackage(int $id)
    {
        $p = Package::query()->available()->find($id);
        if (! $p) {
            $this->error = 'Το πακέτο δεν είναι πλέον διαθέσιμο.';

            return null;
        }

        return $this->store('Πακέτο «'.$p->name.'»', 'package', [['type' => 'package', 'package_id' => $p->id]], [], (int) ($p->duration_days ?: 1));
    }

    protected function store(string $title, string $source, array $specs, array $answers = [], ?int $days = null)
    {
        $this->error = null;
        if (! $this->canSave()) {
            if (! Auth::check()) {
                session()->put(self::SESSION_KEY, $this->answers());
                session()->put('url.intended', route('quote.builder'));

                return $this->redirectRoute('register');
            }
            $this->error = Auth::user()->isCustomer()
                ? 'Επιβεβαιώστε πρώτα το email σας για να αποθηκεύσετε προσφορά.'
                : 'Οι προσφορές αποθηκεύονται από λογαριασμούς πελατών. Ως προσωπικό βλέπετε μόνο τις τιμές.';

            return null;
        }

        try {
            $quote = app(QuoteService::class)->create(Auth::user(), $title, $source, $specs, $days ?? (int) $this->days, $this->startsOn, $answers);
        } catch (InvalidArgumentException $e) {
            $this->error = $e->getMessage();

            return null;
        }

        session()->flash('status', 'Η προσφορά αποθηκεύτηκε στο Studio σας. Μπορείτε να την κατεβάσετε σε PDF ή να την προχωρήσετε.');

        return $this->redirectRoute('quotes.show', $quote);
    }

    public function render()
    {
        $canSee = $this->canSeePrices();
        $calc = new QuoteCalculator;

        $manual = null;
        if ($this->mode === 'manual' && $canSee) {
            $manual = $calc->price($this->manualSpecs(), max(1, min(QuoteCalculator::MAX_DAYS, (int) $this->days)));
        }

        return view('livewire.quote-builder', [
            'canSee' => $canSee,
            'canSave' => $this->canSave(),
            'stations' => $this->sellableStations()->keyBy('id'),
            'allStations' => Station::query()->active()->get(),
            'slots' => AdSlot::query()->active()->whereNotNull('price_per_day')->get()->keyBy('id'),
            'screenList' => Screen::query()->active()->whereNotNull('price_per_day')->get()->keyBy('id'),
            'packages' => Package::query()->available()->get(),
            'manual' => $manual,
            'vatRate' => QuoteCalculator::vatRate(),
            'prices' => [
                'article' => Setting::get('price_sponsored_article'),
                'writing' => Setting::get('price_article_writing'),
                'creative' => collect(QuoteCalculator::CREATIVE)->mapWithKeys(fn ($l, $k) => [$k => Setting::get('price_'.$k)])->all(),
            ],
        ]);
    }
}
