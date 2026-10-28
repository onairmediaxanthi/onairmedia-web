<?php

namespace App\Filament\Resources\AdSlots;

use App\Filament\Resources\AdSlots\Pages\ManageAdSlots;
use App\Models\AdSlot;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\TextInputColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class AdSlotResource extends Resource
{
    protected static ?string $model = AdSlot::class;

    protected static ?string $slug = 'timokatalogos/banner';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedNewspaper;

    protected static ?string $navigationLabel = 'Banner thrakitoday.gr';

    protected static ?string $modelLabel = 'θέση banner';

    protected static ?string $pluralModelLabel = 'Θέσεις banner thrakitoday.gr';

    protected static string|\UnitEnum|null $navigationGroup = 'Τιμοκατάλογος';

    protected static ?int $navigationSort = 20;

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            TextInput::make('name')->label('Όνομα θέσης')->required()->maxLength(100),
            TextInput::make('code')->label('Κωδικός')->required()->alphaDash()->maxLength(40)
                ->unique(ignoreRecord: true)->helperText('Σταθερό αναγνωριστικό, π.χ. sidebar-top.'),
            TextInput::make('dimensions')->label('Διαστάσεις')->maxLength(60)->placeholder('300×250'),
            Select::make('device')->label('Συσκευές')->options(AdSlot::DEVICES)->default('all')->required()->native(false),
            TextInput::make('price_per_day')->label('Τιμή ανά ημέρα')->numeric()->minValue(0)->maxValue(100000)->step(0.01)->suffix('€')
                ->helperText('Χωρίς ΦΠΑ. Κενό = η θέση δεν πωλείται ακόμα online.'),
            TextInput::make('min_days')->label('Ελάχιστες ημέρες')->numeric()->minValue(1)->maxValue(365)->default(1)->required(),
            TextInput::make('max_concurrent')->label('Διαφημιζόμενοι ταυτόχρονα')->numeric()->minValue(1)->maxValue(20)->default(1)->required()
                ->helperText('Πόσα banner εναλλάσσονται στη θέση την ίδια μέρα.'),
            Toggle::make('is_active')->label('Ενεργή')->default(true)->inline(false),
            TextInput::make('description')->label('Περιγραφή για τον πελάτη')->maxLength(300)->columnSpanFull(),
            FileUpload::make('preview_path')->label('Εικόνα που δείχνει τη θέση')->columnSpanFull()
                ->disk('public')->directory('ad-slots')->visibility('public')->image()->maxSize(2048),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort')->reorderable('sort')->paginated(false)
            ->heading('Τιμές ανά ημέρα χωρίς ΦΠΑ · αλλάζουν κατευθείαν στα κελιά')
            ->columns([
                TextColumn::make('name')->label('Θέση')->weight('bold')->description(fn (AdSlot $r) => $r->dimensions),
                TextColumn::make('device')->label('Συσκευές')->formatStateUsing(fn ($state) => AdSlot::DEVICES[$state] ?? $state)->badge(),
                TextInputColumn::make('price_per_day')->label('€ / ημέρα')->type('number')->step(0.01)
                    ->rules(['nullable', 'numeric', 'min:0', 'max:100000'])->width('8rem'),
                TextInputColumn::make('min_days')->label('Ελάχ. ημέρες')->type('number')
                    ->rules(['required', 'integer', 'min:1', 'max:365'])->width('6rem'),
                TextColumn::make('max_concurrent')->label('Ταυτόχρονα'),
                ToggleColumn::make('is_active')->label('Ενεργή'),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageAdSlots::route('/')];
    }
}
