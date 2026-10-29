<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Filament\Resources\Orders\OrderResource;
use App\Models\Order;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;

class ListOrders extends ListRecords
{
    protected static string $resource = OrderResource::class;

    public function getTabs(): array
    {
        $count = fn (array $stages) => Order::query()->whereIn('stage', $stages)->count() ?: null;

        return [
            'action' => Tab::make('Θέλουν ενέργεια')->icon('heroicon-o-bell-alert')
                ->badge($count(Order::NEEDS_ACTION))->badgeColor('danger')
                ->modifyQueryUsing(fn ($query) => $query->whereIn('stage', Order::NEEDS_ACTION)),
            'payment' => Tab::make('Αναμονή πληρωμής')->badge($count(['awaiting_payment']))
                ->modifyQueryUsing(fn ($query) => $query->where('stage', 'awaiting_payment')),
            'progress' => Tab::make('Σε εξέλιξη')->badge($count(['production', 'scheduled', 'running']))
                ->modifyQueryUsing(fn ($query) => $query->whereIn('stage', ['production', 'scheduled', 'running'])),
            'unpaid' => Tab::make('Ανεξόφλητες')
                ->modifyQueryUsing(fn ($query) => $query->whereNull('paid_at')->where('stage', '!=', 'cancelled')),
            'done' => Tab::make('Ολοκληρωμένες')->modifyQueryUsing(fn ($query) => $query->where('stage', 'completed')),
            'cancelled' => Tab::make('Ακυρωμένες')->modifyQueryUsing(fn ($query) => $query->where('stage', 'cancelled')),
            'all' => Tab::make('Όλες'),
        ];
    }

    public function getDefaultActiveTab(): string|int|null
    {
        return Order::query()->whereIn('stage', Order::NEEDS_ACTION)->exists() ? 'action' : 'all';
    }
}
