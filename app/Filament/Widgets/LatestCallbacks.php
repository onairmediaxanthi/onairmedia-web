<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\CallbackRequests\CallbackRequestResource;
use App\Models\CallbackRequest;
use Filament\Facades\Filament;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class LatestCallbacks extends TableWidget
{
    protected static ?int $sort = 2;

    protected static ?string $heading = 'Να τους καλέσουμε';

    public static function canView(): bool
    {
        return (bool) Filament::auth()->user()?->canAccessArea('sales');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(CallbackRequest::query()->where('status', 'new')->with('business')->latest())
            ->paginated([5])
            ->poll('60s')
            ->emptyStateHeading('Κανένα αίτημα σε αναμονή')
            ->recordUrl(fn (CallbackRequest $r) => CallbackRequestResource::getUrl('edit', ['record' => $r]))
            ->columns([
                TextColumn::make('first_name')->label('Όνομα')->weight('bold')
                    ->formatStateUsing(fn ($state, CallbackRequest $r) => $r->fullName())
                    ->description(fn (CallbackRequest $r) => $r->business?->legal_name),
                TextColumn::make('mobile')->label('Κινητό')->url(fn (CallbackRequest $r) => 'tel:+30'.$r->mobile),
                TextColumn::make('created_at')->label('Πότε')->since(),
            ]);
    }
}
