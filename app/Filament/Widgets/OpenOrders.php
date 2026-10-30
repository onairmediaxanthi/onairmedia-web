<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Orders\OrderResource;
use App\Models\Order;
use Filament\Facades\Filament;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class OpenOrders extends TableWidget
{
    protected static ?int $sort = 3;

    protected static ?string $heading = 'Παραγγελίες σε εξέλιξη';

    public static function canView(): bool
    {
        return (bool) Filament::auth()->user()?->canAccessArea('sales');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(Order::query()->open()->with('business')
                ->orderByRaw("case stage when 'received' then 0 when 'payment_review' then 1 when 'awaiting_payment' then 2 else 3 end")
                ->latest())
            ->paginated([5])
            ->emptyStateHeading('Καμία ανοιχτή παραγγελία')
            ->recordUrl(fn (Order $o) => OrderResource::getUrl('view', ['record' => $o]))
            ->columns([
                TextColumn::make('number')->label('Αρ.')->weight('bold')->description(fn (Order $o) => $o->business?->displayName()),
                TextColumn::make('stage')->label('Στάδιο')->badge()
                    ->formatStateUsing(fn ($state, Order $o) => $o->stageLabel())->color(fn (Order $o) => $o->stageInfo()['color']),
                TextColumn::make('total')->label('Σύνολο')->formatStateUsing(fn ($state, Order $o) => $o->money()),
                TextColumn::make('created_at')->label('Πότε')->since(),
            ]);
    }
}
