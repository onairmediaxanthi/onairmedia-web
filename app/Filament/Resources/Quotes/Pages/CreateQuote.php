<?php

namespace App\Filament\Resources\Quotes\Pages;

use App\Filament\Resources\Quotes\QuoteResource;
use App\Models\AdSlot;
use App\Models\Business;
use App\Models\Screen;
use App\Models\Setting;
use App\Models\Station;
use App\Services\Quotes\QuoteCalculator;
use App\Services\Quotes\QuoteService;
use App\Services\Quotes\Recommender;
use App\Support\Audience;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Exceptions\Halt;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;
use InvalidArgumentException;

/**
 * Το Studio Καμπάνιας για το προσωπικό, μέσα στη διαχείριση:
 * ίδιος οδηγός, ίδια μηχανή τιμών, και επιπλέον έκπτωση και συνοδευτικό μήνυμα.
 */
class CreateQuote extends CreateRecord
{
    protected static string $resource = QuoteResource::class;

    protected static ?string $title = 'Νέα προσφορά για πελάτη';

    protected static bool $canCreateAnother = false;

    public function mount(): void
    {
        parent::mount();
        $id = (int) request()->query('pelatis');
        $b = $id ? Business::query()->find($id) : null;
        $this->form->fill([
            'business_id' => $b?->id,
            'title' => $b ? 'Πρόταση προβολής · '.$b->displayName() : null,
            'starts_on' => today()->addDays(3)->format('Y-m-d'),
            'days' => 30, 'goal' => 'awareness', 'budget' => 1000, 'media' => ['radio', 'news', 'led'], 'material' => 'ready', 'seconds' => 30,
            'radio' => [], 'banners' => [], 'screens' => [], 'articles' => 0, 'creative' => [], 'discount' => 0,
        ]);
    }

    protected static function stationOptions(): array
    {
        return Station::query()->active()->orderBy('sort')->get()
            ->filter(fn ($s) => QuoteCalculator::stationSellable($s, true))
            ->mapWithKeys(fn ($s) => [$s->id => QuoteCalculator::stationLabel($s).' · '.QuoteCalculator::money($s->price_spot).($s->is_bookable ? '' : ' (όχι online)')])
            ->all();
    }

    public function form(Schema $schema): Schema
    {
        $live = fn ($c) => $c->live(onBlur: true);

        return $schema->columns(3)->components([
            Group::make()->columnSpan(['default' => 3, 'xl' => 2])->schema([
                Section::make('Πελάτης & καμπάνια')->columns(2)->schema([
                    Select::make('business_id')->label('Πελάτης')->required()->searchable()->columnSpanFull()
                        ->getSearchResultsUsing(fn (string $search) => Business::query()
                            ->where(fn ($q) => $q->where('legal_name', 'like', "%{$search}%")->orWhere('trade_name', 'like', "%{$search}%")->orWhere('vat', 'like', "%{$search}%"))
                            ->limit(30)->get()->mapWithKeys(fn (Business $b) => [$b->id => $b->legal_name.' · ΑΦΜ '.$b->vat])->all())
                        ->getOptionLabelUsing(fn ($value) => ($b = Business::query()->find($value)) ? $b->legal_name.' · ΑΦΜ '.$b->vat : null)
                        ->options(fn () => Business::query()->latest()->limit(20)->get()->mapWithKeys(fn (Business $b) => [$b->id => $b->legal_name.' · ΑΦΜ '.$b->vat])->all())
                        ->helperText(new HtmlString('Δεν υπάρχει; <a class="underline" href="'.e(\App\Filament\Resources\Businesses\BusinessResource::getUrl('create')).'">Νέος πελάτης</a>')),
                    TextInput::make('title')->label('Τίτλος πρότασης')->required()->maxLength(120)->columnSpanFull(),
                    DatePicker::make('starts_on')->label('Έναρξη')->native(false)->minDate(today())->displayFormat('d/m/Y'),
                    $live(TextInput::make('days')->label('Ημέρες')->numeric()->integer()->minValue(1)->maxValue(365)->required()),
                ]),

                Section::make('Οδηγός προτάσεων')->collapsible()->collapsed()->columns(2)
                    ->description('Οι ίδιες ερωτήσεις με τον πελάτη. Πατήστε μια πρόταση για να γεμίσουν αυτόματα τα μέσα από κάτω.')
                    ->schema([
                        Select::make('goal')->label('Στόχος')->options(collect(Recommender::GOALS)->map(fn ($g) => $g['label'])->all())->live(),
                        $live(TextInput::make('budget')->label('Ποσό με ΦΠΑ')->numeric()->minValue(50)->suffix('€')),
                        CheckboxList::make('ages')->label('Ηλικίες')->options(Audience::GROUPS['ages']['options'])->columns(3)->live(),
                        Radio::make('gender')->label('Φύλο')->options(['' => 'Όλοι', 'female' => 'Γυναίκες', 'male' => 'Άνδρες'])->inline()->live(),
                        CheckboxList::make('profiles')->label('Προφίλ')->options(Audience::GROUPS['profiles']['options'])->columns(2)->columnSpanFull()->live(),
                        CheckboxList::make('media')->label('Μέσα')->options(Recommender::MEDIA)->columns(3)->live(),
                        Radio::make('material')->label('Υλικό')->options(['ready' => 'Έχει έτοιμο', 'need' => 'Θέλει δημιουργικό'])->inline()->live(),
                        Select::make('seconds')->label('Διάρκεια σποτ')->options([15 => '15″', 20 => '20″', 30 => '30″', 45 => '45″', 60 => '60″'])->live(),
                        TextEntry::make('wizard_preview')->hiddenLabel()->columnSpanFull()->html()
                            ->state(fn (Get $get) => self::proposalsHtml(self::recommend($get))),
                        Actions::make(collect(['focus' => 'Στοχευμένη', 'combo' => 'Συνδυαστική', 'wide' => 'Μέγιστη κάλυψη'])
                            ->map(fn ($label, $key) => Action::make('use_'.$key)->label('Χρήση: '.$label)->color($key === 'combo' ? 'primary' : 'gray')
                                ->action(fn () => $this->applyProposal($key)))->values()->all())->columnSpanFull(),
                    ]),

                Section::make('Ραδιόφωνο')->schema([
                    Repeater::make('radio')->hiddenLabel()->addActionLabel('Προσθήκη σταθμού')->columns(4)->live()->defaultItems(0)
                        ->schema([
                            Select::make('station_id')->label('Σταθμός')->options(fn () => self::stationOptions())->required()->columnSpan(2)->live()->distinct(),
                            $live(TextInput::make('spots_per_day')->label('Μεταδόσεις/ημέρα')->numeric()->integer()->minValue(0)->maxValue(QuoteCalculator::MAX_SPOTS_PER_DAY)->default(4)->required()),
                            Select::make('seconds')->label('Διάρκεια')->options(collect([10, 15, 20, 25, 30, 40, 45, 60])->mapWithKeys(fn ($s) => [$s => $s.'″'])->all())->default(30)->required()->live(),
                            Select::make('position')->label('Θέση')->options(QuoteCalculator::POSITIONS)->default('standard')->required()->live(),
                            Toggle::make('fixed_hour')->label('Συγκεκριμένες ώρες')->inline(false)->live(),
                            $live(TextInput::make('mentions_live')->label('Αναφορές παραγωγού/ημέρα')->numeric()->integer()->minValue(0)->maxValue(24)->default(0)),
                            $live(TextInput::make('mentions_recorded')->label('Έτοιμες εκφωνήσεις/ημέρα')->numeric()->integer()->minValue(0)->maxValue(24)->default(0)),
                        ]),
                ]),

                Section::make('thrakitoday.gr')->columns(2)->schema([
                    Repeater::make('banners')->label('Θέσεις banner')->addActionLabel('Προσθήκη θέσης')->columns(2)->live()->defaultItems(0)->columnSpanFull()
                        ->schema([
                            Select::make('slot_id')->label('Θέση')->required()->live()->distinct()
                                ->options(fn () => AdSlot::query()->active()->whereNotNull('price_per_day')->get()
                                    ->mapWithKeys(fn ($s) => [$s->id => $s->name.' · '.QuoteCalculator::money($s->price_per_day).'/ημέρα'])->all()),
                            $live(TextInput::make('days')->label('Ημέρες')->numeric()->integer()->minValue(1)->maxValue(365)->placeholder('όλη η καμπάνια')),
                        ]),
                    $live(TextInput::make('articles')->label('Χορηγούμενα άρθρα')->numeric()->integer()->minValue(0)->maxValue(30)),
                    Toggle::make('article_writing')->label('Τα γράφουμε εμείς')->inline(false)->live(),
                ]),

                Section::make('Πινακίδα LED')->schema([
                    Repeater::make('screens')->hiddenLabel()->addActionLabel('Προσθήκη πινακίδας')->columns(2)->live()->defaultItems(0)
                        ->schema([
                            Select::make('screen_id')->label('Πινακίδα')->required()->live()->distinct()
                                ->options(fn () => Screen::query()->active()->whereNotNull('price_per_day')->get()
                                    ->mapWithKeys(fn ($s) => [$s->id => $s->name.' · '.QuoteCalculator::money($s->price_per_day).'/ημέρα'])->all()),
                            $live(TextInput::make('days')->label('Ημέρες')->numeric()->integer()->minValue(1)->maxValue(365)->placeholder('όλη η καμπάνια')),
                        ]),
                ]),

                Section::make('Δημιουργικό, έκπτωση & μήνυμα')->columns(2)->schema([
                    CheckboxList::make('creative')->label('Δημιουργικό')->columnSpanFull()->columns(2)->live()
                        ->options(collect(QuoteCalculator::CREATIVE)->filter(fn ($l, $k) => Setting::get('price_'.$k) !== null)
                            ->map(fn ($l, $k) => $l.' · '.QuoteCalculator::money(Setting::get('price_'.$k)))->all()),
                    $live(TextInput::make('discount')->label('Έκπτωση')->numeric()->minValue(0)->maxValue(QuoteCalculator::MAX_DISCOUNT)->suffix('%')
                        ->helperText('Πάνω στο σύνολο, χωρίς ΦΠΑ. Τη βλέπει ο πελάτης ως γραμμή.')),
                    Textarea::make('intro')->label('Μήνυμα στην αρχή της πρότασης')->rows(4)->maxLength(3000)->columnSpanFull()
                        ->placeholder('π.χ. Όπως συζητήσαμε, σας προτείνουμε έναν συνδυασμό για τις εκπτώσεις του Νοεμβρίου…'),
                ]),
            ]),

            Section::make('Σύνολο')->columnSpan(['default' => 3, 'xl' => 1])->schema([
                TextEntry::make('summary')->hiddenLabel()->html()->state(fn (Get $get) => self::summaryHtml($get)),
            ]),
        ]);
    }

    // ---------------- Υπολογισμοί ----------------

    public static function specsFrom(array $s): array
    {
        $days = max(1, min(QuoteCalculator::MAX_DAYS, (int) ($s['days'] ?? 1)));
        $specs = [];
        foreach ((array) ($s['radio'] ?? []) as $r) {
            if (! empty($r['station_id'])) {
                $specs[] = ['type' => 'radio', 'station_id' => (int) $r['station_id'], 'spots_per_day' => (int) ($r['spots_per_day'] ?? 0),
                    'seconds' => (int) ($r['seconds'] ?? 30), 'position' => (string) ($r['position'] ?? 'standard'), 'fixed_hour' => (bool) ($r['fixed_hour'] ?? false),
                    'mentions_live' => (int) ($r['mentions_live'] ?? 0), 'mentions_recorded' => (int) ($r['mentions_recorded'] ?? 0), 'days' => $days];
            }
        }
        foreach ((array) ($s['banners'] ?? []) as $b) {
            if (! empty($b['slot_id'])) {
                $specs[] = ['type' => 'banner', 'slot_id' => (int) $b['slot_id'], 'days' => (int) (($b['days'] ?? null) ?: $days)];
            }
        }
        foreach ((array) ($s['screens'] ?? []) as $b) {
            if (! empty($b['screen_id'])) {
                $specs[] = ['type' => 'led', 'screen_id' => (int) $b['screen_id'], 'days' => (int) (($b['days'] ?? null) ?: $days)];
            }
        }
        if ((int) ($s['articles'] ?? 0) > 0) {
            $specs[] = ['type' => 'article', 'count' => (int) $s['articles'], 'writing' => (bool) ($s['article_writing'] ?? false)];
        }
        foreach ((array) ($s['creative'] ?? []) as $key) {
            $specs[] = ['type' => 'creative', 'key' => $key];
        }
        if ((float) ($s['discount'] ?? 0) > 0) {
            $specs[] = ['type' => 'discount', 'percent' => (float) $s['discount']];
        }

        return $specs;
    }

    protected static function state(Get $get): array
    {
        return collect(['days', 'radio', 'banners', 'screens', 'articles', 'article_writing', 'creative', 'discount'])
            ->mapWithKeys(fn ($k) => [$k => $get($k)])->all();
    }

    protected static function recommend(Get $get): array
    {
        if ((float) $get('budget') < 50) {
            return ['proposals' => [], 'min_budget' => null];
        }

        return Recommender::forStaff()->recommend([
            'budget' => (float) $get('budget'), 'days' => (int) $get('days') ?: 30, 'goal' => (string) ($get('goal') ?: 'awareness'),
            'ages' => (array) $get('ages'), 'profiles' => (array) $get('profiles'), 'gender' => $get('gender') ?: null,
            'media' => (array) $get('media'), 'material' => (string) ($get('material') ?: 'ready'), 'seconds' => (int) ($get('seconds') ?: 30),
        ]);
    }

    protected static function proposalsHtml(array $out): HtmlString
    {
        if (! $out['proposals']) {
            return new HtmlString('<p style="opacity:.7">'.($out['min_budget']
                ? 'Δεν χωράει πρόταση. Χρειάζεται περίπου '.e(QuoteCalculator::money($out['min_budget'])).' με ΦΠΑ.'
                : 'Συμπληρώστε ποσό, ή ελέγξτε ότι οι σταθμοί έχουν τιμή στον τιμοκατάλογο.').'</p>');
        }
        $h = '<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:12px">';
        foreach ($out['proposals'] as $p) {
            $h .= '<div style="border:1px solid rgba(127,127,127,.3);border-radius:12px;padding:12px">'
                .'<strong>'.e($p['title']).'</strong><ul style="margin:6px 0;font-size:13px;opacity:.85">';
            foreach ($p['priced']['lines'] as $l) {
                $h .= '<li>'.e($l['label']).($l['detail'] ? ' <span style="opacity:.7">· '.e($l['detail']).'</span>' : '').'</li>';
            }
            $h .= '</ul><strong style="color:#c80c92">'.e(QuoteCalculator::money($p['priced']['total'])).'</strong> <span style="opacity:.6;font-size:12px">με ΦΠΑ</span></div>';
        }

        return new HtmlString($h.'</div>');
    }

    protected static function summaryHtml(Get $get): HtmlString
    {
        $p = (new QuoteCalculator(staff: true))->price(self::specsFrom(self::state($get)), (int) $get('days') ?: 1);
        if (! $p['lines']) {
            return new HtmlString('<p style="opacity:.7">Προσθέστε σταθμούς, θέσεις banner ή πινακίδα, ή χρησιμοποιήστε τον οδηγό.</p>');
        }
        $h = '<table style="width:100%;font-size:13px;border-collapse:collapse">';
        foreach ($p['lines'] as $l) {
            $h .= '<tr style="border-bottom:1px solid rgba(127,127,127,.2)"><td style="padding:6px 0"><strong>'.e($l['label']).'</strong>'
                .($l['detail'] ? '<br><span style="opacity:.7">'.e($l['detail']).'</span>' : '').'</td>'
                .'<td style="padding:6px 0;text-align:right;white-space:nowrap">'.e(QuoteCalculator::money($l['amount'])).'</td></tr>';
        }
        $h .= '<tr><td style="padding-top:8px;opacity:.7">Χωρίς ΦΠΑ</td><td style="text-align:right;padding-top:8px">'.e(QuoteCalculator::money($p['subtotal'])).'</td></tr>'
            .'<tr><td style="opacity:.7">ΦΠΑ</td><td style="text-align:right">'.e(QuoteCalculator::money($p['vat'])).'</td></tr>'
            .'<tr><td style="font-size:16px;font-weight:800;padding-top:4px">Σύνολο</td><td style="font-size:16px;font-weight:800;text-align:right;color:#c80c92">'.e(QuoteCalculator::money($p['total'])).'</td></tr></table>';
        foreach ($p['warnings'] as $w) {
            $h .= '<p style="color:#d97706;font-size:12px;margin-top:6px">'.e($w).'</p>';
        }

        return new HtmlString($h);
    }

    /** Γεμίζει τα μέσα της φόρμας από μια πρόταση του οδηγού */
    public function applyProposal(string $key): void
    {
        $state = $this->form->getRawState();
        $get = fn ($k) => data_get($state, $k);
        $out = Recommender::forStaff()->recommend([
            'budget' => (float) $get('budget'), 'days' => (int) $get('days') ?: 30, 'goal' => (string) ($get('goal') ?: 'awareness'),
            'ages' => (array) $get('ages'), 'profiles' => (array) $get('profiles'), 'gender' => $get('gender') ?: null,
            'media' => (array) $get('media'), 'material' => (string) ($get('material') ?: 'ready'), 'seconds' => (int) ($get('seconds') ?: 30),
        ]);
        $p = collect($out['proposals'])->firstWhere('key', $key);
        if (! $p) {
            Notification::make()->warning()->title('Αυτή η πρόταση δεν χωράει στο ποσό')->send();

            return;
        }
        $days = (int) $get('days') ?: 30;
        $radio = $banners = $screens = $creative = [];
        foreach ($p['specs'] as $s) {
            match ($s['type']) {
                'radio' => $radio[] = ['station_id' => $s['station_id'], 'spots_per_day' => $s['spots_per_day'], 'seconds' => $s['seconds'],
                    'position' => $s['position'], 'fixed_hour' => false, 'mentions_live' => 0, 'mentions_recorded' => 0],
                'banner' => $banners[] = ['slot_id' => $s['slot_id'], 'days' => $s['days'] != $days ? $s['days'] : null],
                'led' => $screens[] = ['screen_id' => $s['screen_id'], 'days' => $s['days'] != $days ? $s['days'] : null],
                'creative' => $creative[] = $s['key'],
                default => null,
            };
        }
        $this->form->fill(array_merge($state, [
            'radio' => $radio, 'banners' => $banners, 'screens' => $screens, 'creative' => $creative, 'days' => $days,
            'answers_strategy' => $key,
        ]));
        Notification::make()->success()->title('Η πρόταση «'.$p['title'].'» μπήκε στη φόρμα')->body('Αλλάξτε ό,τι θέλετε πριν την αποθήκευση.')->send();
    }

    protected function handleRecordCreation(array $data): Model
    {
        $business = Business::query()->with('user')->findOrFail($data['business_id']);
        try {
            return app(QuoteService::class)->createForCustomer(
                Filament::auth()->user(), $business->user, $data['title'], self::specsFrom($data), (int) $data['days'],
                $data['starts_on'] ?? null, $data['intro'] ?? null,
                ['wizard' => collect($data)->only(['goal', 'budget', 'ages', 'profiles', 'gender', 'media', 'material', 'seconds'])->all()],
            );
        } catch (InvalidArgumentException $e) {
            Notification::make()->danger()->title($e->getMessage())->send();

            throw new Halt;
        }
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Η προσφορά αποθηκεύτηκε. Πατήστε «Αποστολή στον πελάτη» όταν είναι έτοιμη.';
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }
}
