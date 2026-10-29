<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Quotes\QuoteResource;
use App\Models\Quote;
use Filament\Facades\Filament;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class PendingQuotes extends TableWidget
{
    protected static ?int $sort = 3;

    protected static ?string $heading = 'Πρόσφατες προσφορές';

    public static function canView(): bool
    {
        return (bool) Filament::auth()->user()?->canAccessArea('sales');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(Quote::query()->with('user.business')
                ->orderByRaw("case when status = 'submitted' then 0 else 1 end")->latest())
            ->paginated([5])
            ->emptyStateHeading('Δεν υπάρχουν προσφορές ακόμα')
            ->recordUrl(fn (Quote $q) => QuoteResource::getUrl('edit', ['record' => $q]))
            ->columns([
                TextColumn::make('number')->label('Αρ.')->weight('bold')
                    ->description(fn (Quote $q) => $q->user?->business?->legal_name ?? $q->user?->name),
                TextColumn::make('status')->label('Κατάσταση')->badge()
                    ->formatStateUsing(fn ($state, Quote $q) => $q->statusLabel())
                    ->color(fn ($state, Quote $q) => $q->isExpired() ? 'gray' : (['submitted' => 'danger', 'ordered' => 'success'][$state] ?? 'info')),
                TextColumn::make('total')->label('Σύνολο')->money('EUR', locale: 'el'),
                TextColumn::make('created_at')->label('Πότε')->since(),
            ]);
    }
}
