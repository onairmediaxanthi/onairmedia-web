<?php

namespace App\Filament\Resources\Businesses\RelationManagers;

use App\Filament\Resources\Quotes\QuoteResource;
use App\Models\CustomerActivity;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/** Το ιστορικό ενός πελάτη, μέσα στην καρτέλα του */
class ActivitiesRelationManager extends RelationManager
{
    protected static string $relationship = 'activities';

    protected static ?string $title = 'Δραστηριότητα';

    protected static string|\BackedEnum|null $icon = 'heroicon-o-bell-alert';

    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        return (string) $ownerRecord->activities()->count() ?: null;
    }

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(fn ($query) => $query->with('quote'))
            ->columns([
                TextColumn::make('created_at')->label('Πότε')->dateTime('d/m/Y H:i')->sortable()
                    ->description(fn (CustomerActivity $r) => $r->created_at->locale('el')->diffForHumans()),
                TextColumn::make('type')->label('Ενέργεια')->badge()
                    ->formatStateUsing(fn ($state) => CustomerActivity::TYPES[$state] ?? $state)
                    ->color(fn ($state) => match ($state) {
                        'quote_submitted' => 'success', 'quote_deleted', 'reminders_off' => 'danger',
                        'registered', 'verified', 'activated' => 'info', 'reminder_sent', 'invited' => 'gray', default => 'primary',
                    }),
                TextColumn::make('quote.number')->label('Προσφορά')->fontFamily('mono')->placeholder('—')
                    ->url(fn (CustomerActivity $r) => $r->quote ? QuoteResource::getUrl('view', ['record' => $r->quote]) : null),
                TextColumn::make('meta')->label('Λεπτομέρειες')->wrap()
                    ->state(fn (CustomerActivity $r) => collect((array) $r->meta)->map(fn ($v, $k) => match ($k) {
                        'by' => 'από '.$v, 'code' => 'κωδικός '.$v, 'amount' => number_format((float) $v, 2, ',', '.').' €',
                        'budget' => 'ποσό '.number_format((float) $v, 0, ',', '.').' €', 'days' => $v.' ημέρες', default => null,
                    })->filter()->implode(' · ') ?: null)->placeholder(''),
            ])
            ->filters([
                SelectFilter::make('type')->label('Ενέργεια')->options(CustomerActivity::TYPES)->multiple(),
            ])
            ->paginated([10, 25, 50]);
    }
}
