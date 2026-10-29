<?php

namespace App\Filament\Support;

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



/** Κοινά κομμάτια φόρμας για προσφορές και πακέτα: μέσα, μετατροπή σε specs, ζωντανή σύνοψη τιμής */
class MediaBuilder
{
    public static function stationOptions(): array
    {
        $local = new \App\Services\Quotes\PriceBook('local');
        $metro = new \App\Services\Quotes\PriceBook('metro');

        return Station::query()->active()->orderBy('sort')->get()
            ->filter(fn ($s) => $local->stationSellable($s, true) || $metro->stationSellable($s, true))
            ->mapWithKeys(fn ($s) => [$s->id => QuoteCalculator::stationLabel($s)
                .($local->stationBase($s) ? ' · '.$local->stationPriceLabel($s) : '')
                .($metro->stationBase($s) ? ' · αστικά '.$metro->stationPriceLabel($s) : '')
                .($s->is_bookable ? '' : ' (όχι online)')])
            ->all();
    }

    /** Τα μέσα (ραδιόφωνο, thrakitoday.gr, LED) όπως στο Studio Καμπάνιας */
    public static function sections(bool $withCreative = false): array
    {
        $live = fn ($c) => $c->live(onBlur: true);

        $sections = [
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
                                ->options(fn () => AdSlot::query()->active()->where(fn ($q) => $q->whereNotNull('price_per_day')->orWhereNotNull('metro_price_per_day'))->get()
                                    ->mapWithKeys(fn ($s) => [$s->id => $s->name.($s->price_per_day !== null ? ' · '.QuoteCalculator::money($s->price_per_day).'/ημέρα' : '').($s->metro_price_per_day !== null ? ' · αστικά '.QuoteCalculator::money($s->metro_price_per_day) : '')])->all()),
                            $live(TextInput::make('days')->label('Ημέρες')->numeric()->integer()->minValue(1)->maxValue(365)->placeholder('όλη η καμπάνια')),
                        ]),
                    $live(TextInput::make('articles')->label('Χορηγούμενα άρθρα')->numeric()->integer()->minValue(0)->maxValue(30)),
                    Toggle::make('article_writing')->label('Τα γράφουμε εμείς')->inline(false)->live(),
                ]),

                Section::make('Πινακίδα LED')->schema([
                    Repeater::make('screens')->hiddenLabel()->addActionLabel('Προσθήκη πινακίδας')->columns(2)->live()->defaultItems(0)
                        ->schema([
                            Select::make('screen_id')->label('Πινακίδα')->required()->live()->distinct()
                                ->options(fn () => Screen::query()->active()->where(fn ($q) => $q->whereNotNull('price_per_day')->orWhereNotNull('metro_price_per_day'))->get()
                                    ->mapWithKeys(fn ($s) => [$s->id => $s->name.($s->price_per_day !== null ? ' · '.QuoteCalculator::money($s->price_per_day).'/ημέρα' : '').($s->metro_price_per_day !== null ? ' · αστικά '.QuoteCalculator::money($s->metro_price_per_day) : '')])->all()),
                            $live(TextInput::make('days')->label('Ημέρες')->numeric()->integer()->minValue(1)->maxValue(365)->placeholder('όλη η καμπάνια')),
                        ]),
                ]),
        ];
        if ($withCreative) {
            $sections[] = Section::make('Δημιουργικό')->schema([
                CheckboxList::make('creative')->hiddenLabel()->columns(2)->live()
                    ->options(collect(QuoteCalculator::CREATIVE)->filter(fn ($l, $k) => Setting::get('price_'.$k) !== null)
                        ->map(fn ($l, $k) => $l.' · '.QuoteCalculator::money(Setting::get('price_'.$k)))->all()),
            ]);
        }

        return $sections;
    }

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
        if (! empty($s['package_id'])) {
            $specs[] = ['type' => 'package', 'package_id' => (int) $s['package_id']];
        }
        if ((float) ($s['discount'] ?? 0) > 0) {
            $specs[] = ['type' => 'discount', 'percent' => (float) $s['discount']];
        }

        return $specs;
    }

    public static function summaryHtml(array $state, int $days, ?array $extra = null, string $tier = 'local'): HtmlString
    {
        $p = (new QuoteCalculator(staff: true, tier: $tier))->price(self::specsFrom($state + ['days' => $days]), $days);
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


    /** Τα πεδία των μέσων από specs (αντίστροφο του specsFrom) */
    public static function stateFromSpecs(array $specs, int $days): array
    {
        $s = ['radio' => [], 'banners' => [], 'screens' => [], 'articles' => 0, 'article_writing' => false, 'creative' => [], 'discount' => 0, 'package_id' => null];
        foreach ($specs as $spec) {
            match ($spec['type'] ?? null) {
                'radio' => $s['radio'][] = ['station_id' => $spec['station_id'], 'spots_per_day' => $spec['spots_per_day'] ?? 0, 'seconds' => $spec['seconds'] ?? 30,
                    'position' => $spec['position'] ?? 'standard', 'fixed_hour' => (bool) ($spec['fixed_hour'] ?? false),
                    'mentions_live' => $spec['mentions_live'] ?? 0, 'mentions_recorded' => $spec['mentions_recorded'] ?? 0],
                'banner' => $s['banners'][] = ['slot_id' => $spec['slot_id'], 'days' => ($spec['days'] ?? $days) != $days ? $spec['days'] : null],
                'led' => $s['screens'][] = ['screen_id' => $spec['screen_id'], 'days' => ($spec['days'] ?? $days) != $days ? $spec['days'] : null],
                'article' => [$s['articles'], $s['article_writing']] = [(int) $spec['count'], (bool) ($spec['writing'] ?? false)],
                'creative' => $s['creative'][] = $spec['key'],
                'discount' => $s['discount'] = (float) $spec['percent'],
                'package' => $s['package_id'] = $spec['package_id'],
                default => null,
            };
        }

        return $s;
    }
}
