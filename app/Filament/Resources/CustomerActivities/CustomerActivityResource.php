<?php

namespace App\Filament\Resources\CustomerActivities;

use App\Filament\Resources\CustomerActivities\Pages\ListCustomerActivities;
use App\Models\CustomerActivity;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

/** Τι κάνουν οι πελάτες: εγγραφές, προσφορές, PDF, διαγραφές, υπενθυμίσεις */
class CustomerActivityResource extends Resource
{
    use \App\Filament\Concerns\RestrictedToArea;

    protected static string $area = 'sales';

    protected static ?string $model = CustomerActivity::class;

    protected static ?string $slug = 'drastiriotita';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBellAlert;

    protected static ?string $navigationLabel = 'Δραστηριότητα πελατών';

    protected static ?string $pluralModelLabel = 'Δραστηριότητα πελατών';

    protected static ?string $modelLabel = 'ενέργεια';

    protected static string|\UnitEnum|null $navigationGroup = 'Πωλήσεις';

    protected static ?int $navigationSort = 1;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getNavigationBadge(): ?string
    {
        $n = CustomerActivity::query()->where('created_at', '>=', today())->whereNotIn('type', ['reminder_sent'])->count();

        return $n ? (string) $n : null;
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Σήμερα';
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->poll('60s')
            ->modifyQueryUsing(fn ($query) => $query->with(['user.business', 'quote']))
            ->columns([
                TextColumn::make('created_at')->label('Πότε')->dateTime('d/m H:i')->sortable()
                    ->description(fn (CustomerActivity $r) => $r->created_at->locale('el')->diffForHumans()),
                TextColumn::make('type')->label('Ενέργεια')->badge()
                    ->formatStateUsing(fn ($state) => CustomerActivity::TYPES[$state] ?? $state)
                    ->color(fn ($state) => match ($state) {
                        'quote_submitted' => 'success', 'quote_deleted', 'reminders_off' => 'danger',
                        'registered', 'verified' => 'info', 'reminder_sent' => 'gray', default => 'primary',
                    }),
                TextColumn::make('summary')->label('Λεπτομέρειες')->wrap()->searchable(),
            ])
            ->recordUrl(fn (CustomerActivity $r) => $r->quote ? \App\Filament\Resources\Quotes\QuoteResource::getUrl('view', ['record' => $r->quote])
                : ($r->user?->business ? url('/admin/businesses/'.$r->user->business->id.'/edit') : null))
            ->filters([
                SelectFilter::make('type')->label('Ενέργεια')->options(CustomerActivity::TYPES)->multiple(),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListCustomerActivities::route('/')];
    }
}
