<?php

namespace App\Filament\Resources\RadioPrices;

use App\Filament\Resources\RadioPrices\Pages\EditRadioPrice;
use App\Filament\Resources\RadioPrices\Pages\ListRadioPrices;
use App\Models\Station;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\TextInputColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

/**
 * Τιμοκατάλογος ραδιοφώνων: τα ίδια δεδομένα των σταθμών, μόνο με τα πεδία τιμών.
 * Όλες οι τιμές χωρίς ΦΠΑ.
 */
class RadioPriceResource extends Resource
{
    protected static ?string $model = Station::class;

    protected static ?string $slug = 'timokatalogos/radiofona';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRadio;

    protected static ?string $navigationLabel = 'Ραδιόφωνα';

    protected static ?string $modelLabel = 'τιμές σταθμού';

    protected static ?string $pluralModelLabel = 'Τιμοκατάλογος ραδιοφώνων';

    protected static string|\UnitEnum|null $navigationGroup = 'Τιμοκατάλογος';

    protected static ?int $navigationSort = 10;

    protected static ?string $recordTitleAttribute = 'name';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        $money = fn (string $name, string $label, ?string $help = null) => TextInput::make($name)->label($label)
            ->numeric()->minValue(0)->maxValue(100000)->step(0.01)->suffix('€')->helperText($help);
        $pct = fn (string $name, string $label, ?string $help = null) => TextInput::make($name)->label($label)
            ->numeric()->minValue(0)->maxValue(500)->step(0.5)->suffix('%')->default(0)->helperText($help);

        return $schema->components([
            Section::make('Μεταδόσεις σποτ')->columns(3)->columnSpanFull()
                ->description('Τιμή ανά μία μετάδοση, χωρίς ΦΠΑ.')
                ->schema([
                    $money('price_spot', 'Τιμή μετάδοσης σποτ', 'Για σποτ έως τη διάρκεια βάσης.'),
                    TextInput::make('spot_seconds')->label('Διάρκεια βάσης')->numeric()->minValue(5)->maxValue(120)->suffix('″')->default(30)
                        ->helperText('Μεγαλύτερο σποτ χρεώνεται αναλογικά.'),
                    TextInput::make('max_spots_per_hour')->label('Μέγιστα σποτ πελατών ανά ώρα')->numeric()->minValue(1)->maxValue(60)
                        ->helperText('Όριο διαθεσιμότητας. Κενό = χωρίς όριο.'),
                ]),
            Section::make('Προσαυξήσεις θέσης')->columns(4)->columnSpanFull()
                ->description('Ποσοστό πάνω στην τιμή μετάδοσης. 0 = δεν προσφέρεται χρέωση.')
                ->schema([
                    $pct('pct_first_position', 'Πάντα πρώτη θέση', 'Πρώτο σποτ στο διαφημιστικό διάλειμμα.'),
                    $pct('pct_middle_spot', 'Ενδιάμεσο σποτ', 'Μέσα στο πρόγραμμα, εκτός διαφημιστικού διαλείμματος.'),
                    $pct('pct_sandwich', 'Sandwich', 'Το σποτ στην αρχή και ξανά στο τέλος του ίδιου διαλείμματος.'),
                    $pct('pct_fixed_hour', 'Επιλεγμένη ώρα', 'Όταν ο πελάτης διαλέγει ακριβή ώρα αντί για ζώνη.'),
                ]),
            Section::make('Αναφορές')->columns(2)->columnSpanFull()
                ->description('Τιμή ανά μία αναφορά, χωρίς ΦΠΑ.')
                ->schema([
                    $money('price_mention_live', 'Αναφορά από τον παραγωγό', 'Ο παραγωγός εκφωνεί ζωντανά κείμενο του πελάτη.'),
                    $money('price_mention_recorded', 'Έτοιμη εκφώνηση', 'Ηχογραφημένη ανακοίνωση που μεταδίδεται όπως είναι.'),
                ]),
            Section::make()->columnSpanFull()->schema([
                Toggle::make('is_bookable')->label('Διαθέσιμος για κράτηση online')
                    ->helperText('Ενεργοποιήστε μόλις συμπληρωθεί η τιμή μετάδοσης.'),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        $money = fn (string $name, string $label) => TextInputColumn::make($name)->label($label)
            ->type('number')->rules(['nullable', 'numeric', 'min:0', 'max:100000'])->step(0.01)->width('7rem');
        $pct = fn (string $name, string $label) => TextInputColumn::make($name)->label($label)
            ->type('number')->rules(['required', 'numeric', 'min:0', 'max:500'])->step(0.5)->width('5.5rem')->toggleable();

        return $table
            ->defaultSort('sort')
            ->paginated(false)
            ->heading('Όλες οι τιμές χωρίς ΦΠΑ · αλλάζουν κατευθείαν στα κελιά')
            ->columns([
                ImageColumn::make('logo_path')->label('')->disk('public')->height(32),
                TextColumn::make('name')->label('Σταθμός')->weight('bold')
                    ->description(fn (Station $r) => $r->frequency_label),
                $money('price_spot', 'Σποτ €'),
                TextColumn::make('spot_seconds')->label('Βάση')->suffix('″'),
                $money('price_mention_live', 'Αναφορά παραγωγού €'),
                $money('price_mention_recorded', 'Έτοιμη εκφώνηση €'),
                $pct('pct_first_position', '1η θέση %'),
                $pct('pct_middle_spot', 'Ενδιάμεσο %'),
                $pct('pct_sandwich', 'Sandwich %'),
                $pct('pct_fixed_hour', 'Επιλ. ώρα %'),
                ToggleColumn::make('is_bookable')->label('Online'),
            ])
            ->recordActions([EditAction::make()->label('Όλα')]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRadioPrices::route('/'),
            'edit' => EditRadioPrice::route('/{record}/edit'),
        ];
    }
}
