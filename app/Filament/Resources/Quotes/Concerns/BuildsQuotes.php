<?php

namespace App\Filament\Resources\Quotes\Concerns;

use App\Filament\Support\MediaBuilder;
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
 * Η φόρμα του Studio Καμπάνιας για το προσωπικό (δημιουργία και επεξεργασία προσφοράς).
 */
trait BuildsQuotes
{
    public function form(Schema $schema): Schema
    {
        $live = fn ($c) => $c->live(onBlur: true);

        return $schema->columns(3)->components([
            Group::make()->columnSpan(['default' => 3, 'xl' => 2])->schema([
                Section::make('Πελάτης & καμπάνια')->columns(2)->schema([
                    Select::make('business_id')->label('Πελάτης')->searchable()->columnSpanFull()
                        ->required(fn ($livewire) => ! $livewire instanceof \Filament\Resources\Pages\EditRecord)
                        ->disabled(fn ($livewire) => $livewire instanceof \Filament\Resources\Pages\EditRecord)
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

                ...MediaBuilder::sections(),

                Section::make('Πακέτο, δημιουργικό, έκπτωση & μήνυμα')->columns(2)->schema([
                    Select::make('package_id')->label('Έτοιμο πακέτο')->columnSpanFull()->live()->placeholder('Κανένα')
                        ->options(fn () => \App\Models\Package::query()->available()->whereNotNull('price')->get()
                            ->mapWithKeys(fn ($p) => [$p->id => $p->name.' · '.QuoteCalculator::money($p->price)])->all())
                        ->helperText('Προστίθεται ως γραμμή με την τιμή του πακέτου.'),
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
                TextEntry::make('summary')->hiddenLabel()->html()->state(fn (Get $get) => MediaBuilder::summaryHtml(self::state($get), (int) $get('days') ?: 1)),
            ]),
        ]);
    }

    // ---------------- Υπολογισμοί ----------------

    public static function specsFrom(array $s): array
    {
        return MediaBuilder::specsFrom($s);
    }

    protected static function state(Get $get): array
    {
        return collect(['days', 'radio', 'banners', 'screens', 'articles', 'article_writing', 'creative', 'package_id', 'discount'])
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


    /** Τα πεδία της φόρμας από μια αποθηκευμένη προσφορά */
    public static function stateFromQuote(\App\Models\Quote $quote): array
    {
        $s = MediaBuilder::stateFromSpecs($quote->specs(), (int) $quote->days);
        $w = (array) ($quote->answers['wizard'] ?? []);

        return $s + [
            'business_id' => $quote->user?->business?->id,
            'title' => $quote->title, 'intro' => $quote->intro, 'days' => $quote->days,
            'starts_on' => $quote->starts_on?->isFuture() || $quote->starts_on?->isToday() ? $quote->starts_on->format('Y-m-d') : null,
            'goal' => $w['goal'] ?? 'awareness', 'budget' => $w['budget'] ?? (float) $quote->total, 'ages' => $w['ages'] ?? [], 'profiles' => $w['profiles'] ?? [],
            'gender' => $w['gender'] ?? null, 'media' => $w['media'] ?? ['radio', 'news', 'led'], 'material' => $w['material'] ?? 'ready', 'seconds' => $w['seconds'] ?? 30,
        ];
    }
}
