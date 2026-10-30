<?php

namespace App\Filament\Resources\Orders;

use App\Filament\Resources\Businesses\BusinessResource;
use App\Filament\Resources\Orders\Pages\ListOrders;
use App\Filament\Resources\Orders\Pages\ViewOrder;
use App\Filament\Resources\Quotes\QuoteResource;
use App\Models\Order;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class OrderResource extends Resource
{
    use \App\Filament\Concerns\RestrictedToArea;

    protected static string $area = 'sales';

    protected static ?string $model = Order::class;

    protected static ?string $slug = 'paraggelies';

    protected static ?string $recordRouteKeyName = 'ulid';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingBag;

    protected static ?string $navigationLabel = 'Παραγγελίες';

    protected static ?string $modelLabel = 'παραγγελία';

    protected static ?string $pluralModelLabel = 'Παραγγελίες';

    protected static string|\UnitEnum|null $navigationGroup = 'Πωλήσεις';

    protected static ?int $navigationSort = -1;

    protected static ?string $recordTitleAttribute = 'number';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    /** Πόσες περιμένουν κάτι από εμάς (επιβεβαίωση ή έλεγχο κατάθεσης) */
    public static function getNavigationBadge(): ?string
    {
        $n = Order::query()->whereIn('stage', Order::NEEDS_ACTION)->count();

        return $n ? (string) $n : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Θέλουν ενέργεια: επιβεβαίωση ή έλεγχο κατάθεσης';
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->columns(3)->components([
            Grid::make(1)->columnSpan(2)->schema([
                Section::make()->schema([
                    ViewEntry::make('progress')->hiddenLabel()->view('filament.orders.progress'),
                ]),
                Section::make('Υλικό καμπάνιας')->schema([
                    ViewEntry::make('files')->hiddenLabel()->view('filament.orders.files'),
                ]),
                Section::make('Ιστορικό')->description('Ό,τι δεν είναι «εσωτερικό» το βλέπει και ο πελάτης στο Studio του.')->schema([
                    ViewEntry::make('timeline')->hiddenLabel()->view('filament.orders.timeline'),
                ]),
            ]),
            Grid::make(1)->columnSpan(1)->schema([
                Section::make('Πελάτης')->schema([
                    TextEntry::make('business.legal_name')->label('Επωνυμία')->weight('bold')
                        ->url(fn (Order $r) => $r->business ? BusinessResource::getUrl('edit', ['record' => $r->business]) : null),
                    TextEntry::make('business.contact_person')->label('Υπεύθυνος')
                        ->formatStateUsing(fn ($state, Order $r) => $state.' · '.$r->business?->mobile),
                    TextEntry::make('user.email')->label('Email')->copyable(),
                    TextEntry::make('quote.number')->label('Προσφορά')
                        ->formatStateUsing(fn ($state, Order $r) => $state.' · '.$r->quote?->title)
                        ->url(fn (Order $r) => $r->quote ? QuoteResource::getUrl('view', ['record' => $r->quote]) : null),
                ]),
                Section::make('Πληρωμή')->schema([
                    TextEntry::make('total')->label('Σύνολο με ΦΠΑ')->formatStateUsing(fn ($state, Order $r) => $r->money())->weight('bold')->size('lg'),
                    TextEntry::make('payment_method')->label('Τρόπος')->formatStateUsing(fn ($state, Order $r) => $r->methodLabel()),
                    TextEntry::make('paid_amount')->label('Πληρώθηκαν')
                        ->formatStateUsing(fn ($state, Order $r) => $r->money('paid_amount').($r->isPaid() ? ' · εξοφλήθηκε '.$r->paid_at->format('d/m/Y') : ' · υπόλοιπο '.$r->money('balance')))
                        ->color(fn (Order $r) => $r->isPaid() ? 'success' : 'warning'),
                    TextEntry::make('payment_due_on')->label('Προθεσμία')->date('d/m/Y')->placeholder('—')
                        ->color(fn (Order $r) => ! $r->isPaid() && $r->payment_due_on?->isPast() ? 'danger' : null),
                    TextEntry::make('deposit_declared_at')->label('Δήλωση κατάθεσης')->dateTime('d/m/Y H:i')->placeholder('—')
                        ->helperText(fn (Order $r) => $r->deposit_note),
                    TextEntry::make('receipt_path')->label('Αποδεικτικό')->state(fn (Order $r) => $r->receipt_path ? 'Ανέβηκε · «Αποδεικτικό κατάθεσης» πάνω' : 'Όχι')
                        ->color(fn (Order $r) => $r->receipt_path ? 'success' : 'gray'),
                ]),
                Section::make('Πρόγραμμα & τιμολόγιο')->schema([
                    TextEntry::make('starts_on')->label('Διάρκεια')->placeholder('Δεν έχει οριστεί')
                        ->formatStateUsing(fn ($state, Order $r) => $r->starts_on?->format('d/m/Y').($r->ends_on ? ' έως '.$r->ends_on->format('d/m/Y') : '')),
                    TextEntry::make('invoice_number')->label('Τιμολόγιο')->placeholder('—')
                        ->formatStateUsing(fn ($state, Order $r) => $state.($r->invoice_path ? ' · PDF ✓' : '')),
                    TextEntry::make('admin_notes')->label('Εσωτερικές σημειώσεις')->placeholder('—'),
                ]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(fn ($query) => $query->with(['business', 'quote']))
            ->columns([
                TextColumn::make('number')->label('Αρ.')->searchable()->weight('bold')
                    ->description(fn (Order $r) => $r->business?->displayName()),
                TextColumn::make('stage')->label('Στάδιο')->badge()
                    ->formatStateUsing(fn ($state, Order $r) => $r->stageLabel())
                    ->color(fn (Order $r) => $r->stageInfo()['color'])
                    ->icon(fn (Order $r) => $r->stageInfo()['icon']),
                TextColumn::make('total')->label('Σύνολο')->formatStateUsing(fn ($state, Order $r) => $r->money())->sortable()
                    ->description(fn (Order $r) => $r->methodLabel()),
                IconColumn::make('paid_at')->label('Εξόφληση')->boolean()->state(fn (Order $r) => $r->isPaid())
                    ->falseIcon('heroicon-o-clock')->falseColor(fn (Order $r) => ! $r->isCancelled() && $r->payment_due_on?->isPast() ? 'danger' : 'gray')
                    ->tooltip(fn (Order $r) => $r->isPaid() ? 'Εξοφλήθηκε '.$r->paid_at->format('d/m/Y') : 'Υπόλοιπο '.$r->money('balance')),
                TextColumn::make('starts_on')->label('Πρόγραμμα')->placeholder('—')
                    ->formatStateUsing(fn ($state, Order $r) => $r->starts_on?->format('d/m').($r->ends_on ? ' – '.$r->ends_on->format('d/m/Y') : '')),
                TextColumn::make('created_at')->label('Παραγγέλθηκε')->dateTime('d/m/Y')->sortable()
                    ->description(fn (Order $r) => $r->created_at->locale('el')->diffForHumans()),
            ])
            ->filters([
                SelectFilter::make('stage')->label('Στάδιο')->options(collect(Order::STAGES)->map(fn ($s) => $s['staff'])->all()),
                SelectFilter::make('payment_method')->label('Τρόπος πληρωμής')->options(Order::METHODS),
            ])
            ->recordActions([ViewAction::make()->label('Άνοιγμα')]);
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['number', 'business.legal_name', 'business.vat'];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOrders::route('/'),
            'view' => ViewOrder::route('/{record}'),
        ];
    }
}
