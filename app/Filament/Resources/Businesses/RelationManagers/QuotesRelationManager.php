<?php

namespace App\Filament\Resources\Businesses\RelationManagers;

use App\Filament\Resources\Quotes\QuoteResource;
use App\Models\Quote;
use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

/** Οι προσφορές ενός πελάτη, μέσα στην καρτέλα του */
class QuotesRelationManager extends RelationManager
{
    protected static string $relationship = 'quotes';

    protected static ?string $title = 'Προσφορές';

    protected static string|\BackedEnum|null $icon = 'heroicon-o-document-text';

    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        return (string) $ownerRecord->quotes()->count() ?: null;
    }

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(fn ($query) => $query->withoutGlobalScopes([SoftDeletingScope::class])->with('createdBy'))
            ->recordUrl(fn (Quote $r) => QuoteResource::getUrl('view', ['record' => $r]))
            ->columns([
                TextColumn::make('number')->label('Αριθμός')->fontFamily('mono')->description(fn (Quote $r) => $r->title),
                TextColumn::make('total')->label('Σύνολο')->money('EUR', locale: 'el'),
                TextColumn::make('status')->label('Κατάσταση')->badge()
                    ->formatStateUsing(fn ($state, Quote $r) => $r->trashed() ? 'Διαγράφηκε' : $r->statusLabel())
                    ->color(fn ($state, Quote $r) => $r->trashed() ? 'danger' : ($r->isExpired() ? 'gray' : match ($state) { 'submitted' => 'warning', 'ordered' => 'success', default => 'info' })),
                TextColumn::make('source')->label('Πηγή')->formatStateUsing(fn ($state) => Quote::SOURCES[$state] ?? $state)
                    ->description(fn (Quote $r) => $r->createdBy?->name),
                TextColumn::make('created_at')->label('Δημιουργία')->dateTime('d/m/Y H:i')->sortable(),
                TextColumn::make('valid_until')->label('Λήξη')->date('d/m/Y'),
            ])
            ->filters([TrashedFilter::make()->label('Διαγραμμένες')])
            ->headerActions([
                Action::make('new')->label('Νέα προσφορά')->icon('heroicon-o-document-plus')
                    ->url(fn () => QuoteResource::getUrl('create', ['pelatis' => $this->getOwnerRecord()->id])),
            ]);
    }
}
