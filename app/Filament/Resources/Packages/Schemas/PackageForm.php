<?php

namespace App\Filament\Resources\Packages\Schemas;

use App\Filament\Support\MediaBuilder;
use App\Models\Package;
use App\Services\Quotes\QuoteCalculator;
use Filament\Forms\Components\Select;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Group;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class PackageForm
{
    /** Από τα μέσα του builder: specs, λίστα παροχών, μέσα και (αν ζητήθηκε) τιμή */
    public static function applyBuilder(array $data): array
    {
        $days = max(1, (int) ($data['duration_days'] ?? 0) ?: 30);
        $specs = QuoteCalculator::customerSpecs(MediaBuilder::specsFrom((array) ($data['builder'] ?? []) + ['days' => $days]));
        $specs = array_values(array_filter($specs, fn ($s) => $s['type'] !== 'package'));
        unset($data['builder'], $data['builder_summary']);
        $data['specs'] = $specs ?: null;

        if ($specs) {
            $priced = (new QuoteCalculator(staff: true))->price($specs, $days);
            $data['duration_days'] = $data['duration_days'] ?: $days;
            if (empty(array_filter((array) ($data['includes'] ?? [])))) {
                $data['includes'] = array_map(fn ($l) => trim($l['label'].($l['detail'] ? ' · '.$l['detail'] : '')), $priced['lines']);
            }
            if (empty($data['media_mix'])) {
                $mix = collect($specs)->map(fn ($s) => match ($s['type']) {
                    'radio' => 'radio', 'banner', 'article' => 'news', 'led' => 'led', 'creative' => 'creative', default => null,
                })->filter()->unique()->values()->all();
                $data['media_mix'] = implode(',', $mix);
            }
            if (($data['pricing'] ?? 'fixed') === 'auto') {
                $list = $priced['subtotal'];
                $pct = max(0, min(90, (float) ($data['discount_percent'] ?? 0)));
                $data['price'] = round($list * (1 - $pct / 100), 0);
                $data['compare_at_price'] = $pct > 0 ? round($list, 0) : null;
            }
        } elseif (($data['pricing'] ?? 'fixed') === 'auto') {
            $data['pricing'] = 'fixed';
        }

        return $data;
    }

    public static function fillBuilder(array $data): array
    {
        $data['builder'] = MediaBuilder::stateFromSpecs((array) ($data['specs'] ?? []), (int) ($data['duration_days'] ?? 30) ?: 30);

        return $data;
    }

    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Το πακέτο')->columns(2)->columnSpanFull()->schema([
                TextInput::make('name')->label('Όνομα')->required()->maxLength(80)
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn (Set $set, ?string $state, string $operation) => $operation === 'create'
                        ? $set('slug', Str::slug(Str::ascii((string) $state))) : null),
                TextInput::make('slug')->label('Κωδικός URL')->required()->alphaDash()->unique(ignoreRecord: true)->maxLength(80),
                TextInput::make('tagline')->label('Σύντομη περιγραφή')->maxLength(140)->columnSpanFull()
                    ->placeholder('π.χ. Ραδιόφωνο και Οθόνη Πόλης στην ίδια πόλη'),
                Textarea::make('description')->label('Αναλυτική περιγραφή')->rows(3)->maxLength(1000)->columnSpanFull(),
                CheckboxList::make('media_mix')->label('Μέσα που περιλαμβάνει')->options(Package::MEDIA)->columns(4)->columnSpanFull()
                    ->formatStateUsing(fn ($state) => is_string($state) ? array_filter(explode(',', $state)) : ($state ?? []))
                    ->dehydrateStateUsing(fn ($state) => implode(',', (array) $state)),
                Repeater::make('includes')->label('Τι περιλαμβάνει (λίστα)')->helperText('Κενό = φτιάχνεται αυτόματα από τα μέσα του πακέτου.')->simple(
                    TextInput::make('line')->required()->maxLength(120)->placeholder('π.χ. 5 μεταδόσεις την ημέρα σε 3 σταθμούς'),
                )->addActionLabel('Προσθήκη γραμμής')->reorderable()->columnSpanFull()->defaultItems(0),
                TextInput::make('duration_days')->label('Διάρκεια (ημέρες)')->numeric()->minValue(1)->maxValue(366),
            ]),

            Section::make('Τι περιλαμβάνει, από τον τιμοκατάλογο')->columnSpanFull()->collapsible()
                ->description('Διαλέξτε μέσα όπως στο Studio Καμπάνιας (ένα ή συνδυασμό). Η λίστα «Τι περιλαμβάνει» και η τιμή συμπληρώνονται αυτόματα με την αποθήκευση. Οι ημέρες είναι η «Διάρκεια» από πάνω.')
                ->schema([
                    Group::make(MediaBuilder::sections(withCreative: true))->statePath('builder'),
                    TextEntry::make('builder_summary')->label('Αξία από τον τιμοκατάλογο')->html()
                        ->state(fn (Get $get) => MediaBuilder::summaryHtml((array) $get('builder'), (int) $get('duration_days') ?: 1)),
                ]),

            Section::make('Τιμή')->columns(3)->columnSpanFull()
                ->description('Οι τιμές φαίνονται μόνο σε συνδεδεμένους επαγγελματίες. Οι επισκέπτες βλέπουν «Δείτε την τιμή».')
                ->schema([
                    Select::make('pricing')->label('Πώς ορίζεται η τιμή')->default('fixed')->required()->live()->native(false)
                        ->options(['auto' => 'Από τον τιμοκατάλογο, με έκπτωση %', 'fixed' => 'Δική μου τιμή']),
                    TextInput::make('discount_percent')->label('Έκπτωση προσφοράς')->numeric()->minValue(0)->maxValue(90)->suffix('%')
                        ->visible(fn (Get $get) => $get('pricing') === 'auto')->live(onBlur: true)
                        ->helperText('Η αρχική αξία φαίνεται διαγραμμένη δίπλα στην τιμή.'),
                    TextInput::make('price')->label('Τιμή (χωρίς ΦΠΑ)')->numeric()->minValue(0)->suffix('€')
                        ->required(fn (Get $get) => $get('pricing') !== 'auto')
                        ->disabled(fn (Get $get) => $get('pricing') === 'auto')->dehydrated()
                        ->helperText(fn (Get $get) => $get('pricing') === 'auto' ? 'Υπολογίζεται με την αποθήκευση.' : null),
                    TextInput::make('compare_at_price')->label('Αρχική τιμή (για έκπτωση)')->numeric()->minValue(0)->suffix('€')
                        ->visible(fn (Get $get) => $get('pricing') !== 'auto')
                        ->gt('price')->helperText('Συμπληρώστε μόνο σε προσφορά: εμφανίζεται διαγραμμένη.'),
                    TextInput::make('badge')->label('Ετικέτα')->maxLength(30)->placeholder('π.χ. -20% ή Black Friday'),
                ]),

            Section::make('Εμφάνιση & διάρκεια προσφοράς')->columns(3)->columnSpanFull()->schema([
                ColorPicker::make('color')->label('Χρώμα')->default('#c80c92'),
                FileUpload::make('image_path')->label('Εικόνα (προαιρετική)')
                    ->disk('public')->directory('packages')->visibility('public')->image()->imageEditor()->maxSize(3072),
                Toggle::make('is_featured')->label('Προβεβλημένο')->inline(false)
                    ->helperText('Ξεχωρίζει στην αρχική.'),
                Toggle::make('is_offer')->label('Είναι προσφορά')->inline(false)->live(),
                DateTimePicker::make('available_from')->label('Διαθέσιμο από')->seconds(false)->native(false),
                DateTimePicker::make('available_until')->label('Λήξη προσφοράς')->seconds(false)->native(false)->after('available_from')
                    ->required(fn (Get $get) => (bool) $get('is_offer')),
                Toggle::make('is_active')->label('Ενεργό')->default(true)->inline(false),
            ]),
        ]);
    }
}
