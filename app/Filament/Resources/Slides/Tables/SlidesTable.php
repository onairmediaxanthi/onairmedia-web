<?php

namespace App\Filament\Resources\Slides\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class SlidesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('sort')
            ->reorderable('sort')
            ->paginated(false)
            ->columns([
                ImageColumn::make('image_path')->label('')->disk('public')->height(48)->width(110),
                TextColumn::make('title')->label('Τίτλος')->weight('bold')->wrap()
                    ->description(fn ($record) => $record->subtitle),
                TextColumn::make('ends_at')->label('Λήξη')->dateTime('d/m/Y H:i')->placeholder('Χωρίς λήξη')
                    ->color(fn ($record) => $record->ends_at?->isPast() ? 'danger' : null),
                ToggleColumn::make('is_active')->label('Ενεργό'),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }
}
