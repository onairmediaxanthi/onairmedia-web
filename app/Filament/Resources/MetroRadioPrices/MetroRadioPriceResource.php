<?php

namespace App\Filament\Resources\MetroRadioPrices;

use App\Filament\Resources\MetroRadioPrices\Pages\EditMetroRadioPrice;
use App\Filament\Resources\MetroRadioPrices\Pages\ListMetroRadioPrices;
use App\Models\Station;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\TextInputColumn;
use Filament\Tables\Table;

/**
 * Τιμοκατάλογος μεγάλων αστικών κέντρων: χρέωση ανά δευτερόλεπτο.
 * Οι προσαυξήσεις θέσης (%) και η διάθεση online είναι κοινές με τον βασικό.
 */
class MetroRadioPriceResource extends Resource
{
    use \App\Filament\Concerns\RestrictedToArea;

    protected static string $area = 'pricing';

    protected static ?string $model = Station::class;

    protected static ?string $slug = 'timokatalogos/astika-radiofona';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;

    protected static ?string $navigationLabel = 'Ραδιόφωνα · αστικά κέντρα';

    protected static ?string $modelLabel = 'τιμές σταθμού (αστικά κέντρα)';

    protected static ?string $pluralModelLabel = 'Τιμοκατάλογος αστικών κέντρων · ραδιόφωνα';

    protected static string|\UnitEnum|null $navigationGroup = 'Τιμοκατάλογος';

    protected static ?int $navigationSort = 11;

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
        return $schema->components([
            Section::make('Μεγάλα αστικά κέντρα')->columns(3)->columnSpanFull()
                ->description('Ισχύει για πελάτες από τις πόλεις που ορίζετε στις «Λοιπές χρεώσεις». Χωρίς ΦΠΑ. Οι προσαυξήσεις θέσης (%) είναι ίδιες με τον βασικό τιμοκατάλογο.')
                ->schema([
                    TextInput::make('metro_price_second')->label('Τιμή ανά δευτερόλεπτο')->numeric()->minValue(0)->maxValue(1000)->step(0.0001)->suffix('€')
                        ->helperText('π.χ. 0,15 € → σποτ 30″ = 4,50 €'),
                    TextInput::make('metro_mention_live')->label('Αναφορά από τον παραγωγό')->numeric()->minValue(0)->step(0.01)->suffix('€'),
                    TextInput::make('metro_mention_recorded')->label('Έτοιμη εκφώνηση')->numeric()->minValue(0)->step(0.01)->suffix('€'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort')
            ->paginated(false)
            ->heading('Χρέωση ανά δευτερόλεπτο μετάδοσης · χωρίς ΦΠΑ · αλλάζουν κατευθείαν στα κελιά')
            ->columns([
                ImageColumn::make('logo_path')->label('')->disk('public')->height(32),
                TextColumn::make('name')->label('Σταθμός')->weight('bold')->description(fn (Station $r) => $r->frequency_label),
                TextInputColumn::make('metro_price_second')->label('€ / δευτερόλεπτο')->type('number')->step(0.0001)
                    ->rules(['nullable', 'numeric', 'min:0', 'max:1000'])->width('8rem'),
                TextColumn::make('example')->label('Σποτ 30″')->state(fn (Station $r) => $r->metro_price_second !== null
                    ? \App\Services\Quotes\QuoteCalculator::money((float) $r->metro_price_second * 30) : '—'),
                TextInputColumn::make('metro_mention_live')->label('Αναφορά παραγωγού €')->type('number')->step(0.01)
                    ->rules(['nullable', 'numeric', 'min:0', 'max:100000'])->width('7rem'),
                TextInputColumn::make('metro_mention_recorded')->label('Έτοιμη εκφώνηση €')->type('number')->step(0.01)
                    ->rules(['nullable', 'numeric', 'min:0', 'max:100000'])->width('7rem'),
                TextColumn::make('is_bookable')->label('Online')->badge()
                    ->formatStateUsing(fn ($state) => $state ? 'Ναι' : 'Όχι')->color(fn ($state) => $state ? 'success' : 'gray'),
            ])
            ->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMetroRadioPrices::route('/'),
            'edit' => EditMetroRadioPrice::route('/{record}/edit'),
        ];
    }
}
