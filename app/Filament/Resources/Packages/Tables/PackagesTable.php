<?php

namespace App\Filament\Resources\Packages\Tables;

use App\Models\Package;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ReplicateAction;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class PackagesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('sort')
            ->reorderable('sort')
            ->columns([
                ColorColumn::make('color')->label(''),
                TextColumn::make('name')->label('Πακέτο')->weight('bold')->searchable()
                    ->description(fn (Package $r) => $r->tagline),
                TextColumn::make('price')->label('Τιμή')->formatStateUsing(fn ($state) => Package::formatPrice($state))
                    ->description(fn (Package $r) => $r->discount_percent ? "−{$r->discount_percent}%" : null),
                TextColumn::make('available_until')->label('Λήξη')->dateTime('d/m/Y H:i')->placeholder('—')
                    ->color(fn (Package $r) => $r->available_until?->isPast() ? 'danger' : null),
                ToggleColumn::make('is_featured')->label('Προβολή'),
                ToggleColumn::make('is_active')->label('Ενεργό'),
            ])
            ->filters([
                TernaryFilter::make('is_offer')->label('Προσφορές'),
            ])
            ->recordActions([
                EditAction::make(),
                ReplicateAction::make()->label('Αντίγραφο')
                    ->mutateRecordDataUsing(fn (array $data) => [...$data, 'name' => $data['name'].' (αντίγραφο)', 'slug' => $data['slug'].'-'.now()->format('His'), 'is_active' => false]),
                DeleteAction::make(),
            ]);
    }
}
