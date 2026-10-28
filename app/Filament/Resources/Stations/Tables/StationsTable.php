<?php

namespace App\Filament\Resources\Stations\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class StationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('sort')
            ->reorderable('sort')
            ->paginated(false)
            ->columns([
                ImageColumn::make('logo_path')->label('')->disk('public')->height(40),
                TextColumn::make('name')->label('Σταθμός')->searchable()->weight('bold')
                    ->description(fn ($record) => $record->slogan),
                TextColumn::make('frequency_label')->label('Συχνότητα'),
                TextColumn::make('genre')->label('Είδος')->toggleable(),
                ColorColumn::make('color')->label('Χρώμα'),
                ToggleColumn::make('is_active')->label('Ενεργός'),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
