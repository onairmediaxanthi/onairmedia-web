<?php

namespace App\Filament\Resources\Stations;

use App\Filament\Resources\Stations\Pages\CreateStation;
use App\Filament\Resources\Stations\Pages\EditStation;
use App\Filament\Resources\Stations\Pages\ListStations;
use App\Filament\Resources\Stations\Schemas\StationForm;
use App\Filament\Resources\Stations\Tables\StationsTable;
use App\Models\Station;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class StationResource extends Resource
{
    use \App\Filament\Concerns\RestrictedToArea;

    protected static string $area = 'media';

    protected static ?string $model = Station::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRadio;

    protected static ?string $modelLabel = 'σταθμός';

    protected static ?string $pluralModelLabel = 'Σταθμοί';

    protected static string|\UnitEnum|null $navigationGroup = 'Μέσα';

    protected static ?int $navigationSort = 10;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return StationForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return StationsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListStations::route('/'),
            'create' => CreateStation::route('/create'),
            'edit' => EditStation::route('/{record}/edit'),
        ];
    }
}
