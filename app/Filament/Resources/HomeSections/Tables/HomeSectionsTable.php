<?php

namespace App\Filament\Resources\HomeSections\Tables;

use App\Models\HomeSection;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ReplicateAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class HomeSectionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('sort')
            ->reorderable('sort')
            ->paginated(false)
            ->description('Η σειρά εδώ είναι η σειρά στην αρχική. Σύρετε για αλλαγή.')
            ->columns([
                TextColumn::make('label')->label('Ενότητα')->weight('bold')
                    ->state(fn (HomeSection $r) => $r->label())
                    ->description(fn (HomeSection $r) => HomeSection::TYPES[$r->type] ?? $r->type),
                ToggleColumn::make('show_mobile')->label('Κινητό'),
                ToggleColumn::make('show_tablet')->label('Tablet'),
                ToggleColumn::make('show_desktop')->label('Υπολογιστής'),
                ToggleColumn::make('is_active')->label('Ενεργή'),
                TextColumn::make('ends_at')->label('Λήξη')->dateTime('d/m/Y')->placeholder('—')->toggleable(),
            ])
            ->recordActions([
                EditAction::make(),
                ReplicateAction::make()->label('Αντίγραφο')
                    ->visible(fn (HomeSection $r) => ! in_array($r->type, HomeSection::SINGLETONS, true))
                    ->beforeReplicaSaved(fn (HomeSection $replica) => $replica->fill([
                        'is_active' => false,
                        'sort' => (HomeSection::max('sort') ?? 0) + 10,
                    ])),
                DeleteAction::make(),
            ]);
    }
}
