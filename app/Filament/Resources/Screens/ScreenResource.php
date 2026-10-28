<?php

namespace App\Filament\Resources\Screens;

use App\Filament\Resources\Screens\Pages\ManageScreens;
use App\Models\Screen;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\TextInputColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class ScreenResource extends Resource
{
    protected static ?string $model = Screen::class;

    protected static ?string $slug = 'timokatalogos/pinakides';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTv;

    protected static ?string $navigationLabel = 'Πινακίδες LED';

    protected static ?string $modelLabel = 'πινακίδα';

    protected static ?string $pluralModelLabel = 'Πινακίδες LED';

    protected static string|\UnitEnum|null $navigationGroup = 'Τιμοκατάλογος';

    protected static ?int $navigationSort = 30;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Η πινακίδα')->columns(2)->columnSpanFull()->schema([
                TextInput::make('name')->label('Όνομα')->required()->maxLength(100)->live(onBlur: true)
                    ->afterStateUpdated(fn (Set $set, ?string $state, string $operation) => $operation === 'create' ? $set('slug', Str::slug($state ?? '')) : null),
                TextInput::make('slug')->label('Διεύθυνση σελίδας')->required()->alphaDash()->maxLength(60)->unique(ignoreRecord: true),
                TextInput::make('location')->label('Τοποθεσία')->maxLength(200)->placeholder('π.χ. Κεντρική πλατεία Ξάνθης'),
                TextInput::make('map_url')->label('Link χάρτη')->url()->maxLength(500)->rule('starts_with:https://'),
                TextInput::make('dimensions')->label('Διαστάσεις')->maxLength(60)->placeholder('π.χ. 4×3 μ.'),
                TextInput::make('resolution')->label('Ανάλυση (pixels)')->maxLength(30)->placeholder('π.χ. 1280×960')
                    ->helperText('Με βάση αυτήν ετοιμάζεται το γραφικό.'),
                Textarea::make('description')->label('Περιγραφή για τον πελάτη')->rows(3)->maxLength(1000)->columnSpanFull(),
                FileUpload::make('photo_path')->label('Φωτογραφία')->columnSpanFull()
                    ->disk('public')->directory('screens')->visibility('public')->image()->imageEditor()->maxSize(4096),
            ]),
            Section::make('Προβολή & τιμή')->columns(3)->columnSpanFull()->schema([
                TextInput::make('price_per_day')->label('Τιμή προβολής ανά ημέρα')->numeric()->minValue(0)->maxValue(100000)->step(0.01)->suffix('€')
                    ->helperText('Χωρίς ΦΠΑ.'),
                TextInput::make('min_days')->label('Ελάχιστες ημέρες')->numeric()->minValue(1)->maxValue(365)->default(1)->required(),
                TextInput::make('max_advertisers')->label('Μέγιστοι διαφημιζόμενοι')->numeric()->minValue(1)->maxValue(100)
                    ->helperText('Πόσα γραφικά χωράει ο κύκλος προβολής.'),
                TextInput::make('slot_seconds')->label('Διάρκεια προβολής')->numeric()->minValue(3)->maxValue(120)->default(10)->suffix('″')->required(),
                TextInput::make('loops_per_hour')->label('Προβολές ανά ώρα')->numeric()->minValue(1)->maxValue(720)
                    ->helperText('Πόσες φορές ανά ώρα εμφανίζεται κάθε γραφικό.'),
                Toggle::make('is_active')->label('Ενεργή')->default(true)->inline(false),
                TimePicker::make('operating_from')->label('Λειτουργεί από')->seconds(false),
                TimePicker::make('operating_to')->label('Λειτουργεί έως')->seconds(false),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort')->reorderable('sort')->paginated(false)
            ->columns([
                TextColumn::make('name')->label('Πινακίδα')->weight('bold')->description(fn (Screen $r) => $r->location),
                TextInputColumn::make('price_per_day')->label('€ / ημέρα')->type('number')->step(0.01)
                    ->rules(['nullable', 'numeric', 'min:0', 'max:100000'])->width('8rem'),
                TextColumn::make('slot_seconds')->label('Προβολή')->suffix('″'),
                TextColumn::make('loops_per_hour')->label('Φορές/ώρα')->placeholder('—'),
                ToggleColumn::make('is_active')->label('Ενεργή'),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageScreens::route('/')];
    }
}
