<?php

namespace App\Filament\Resources\CallbackRequests;

use App\Filament\Resources\Businesses\BusinessResource;
use App\Filament\Resources\CallbackRequests\Pages\EditCallbackRequest;
use App\Filament\Resources\CallbackRequests\Pages\ListCallbackRequests;
use App\Models\CallbackRequest;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\ToggleButtons;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CallbackRequestResource extends Resource
{
    use \App\Filament\Concerns\RestrictedToArea;

    protected static string $area = 'sales';

    protected static ?string $model = CallbackRequest::class;

    protected static ?string $slug = 'aitimata';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPhone;

    protected static ?string $navigationLabel = 'Αιτήματα επικοινωνίας';

    protected static ?string $modelLabel = 'αίτημα';

    protected static ?string $pluralModelLabel = 'Αιτήματα επικοινωνίας';

    protected static string|\UnitEnum|null $navigationGroup = 'Πωλήσεις';

    protected static ?int $navigationSort = 0;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getNavigationBadge(): ?string
    {
        $n = CallbackRequest::query()->where('status', 'new')->count();

        return $n ? (string) $n : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Αίτημα')->columns(3)->columnSpanFull()->schema([
                TextEntry::make('name')->label('Όνομα')->state(fn (CallbackRequest $r) => $r->fullName())->weight('bold'),
                TextEntry::make('mobile')->label('Κινητό')->copyable()
                    ->url(fn (CallbackRequest $r) => 'tel:+30'.$r->mobile),
                TextEntry::make('vat')->label('ΑΦΜ')->copyable()->helperText('Ο αριθμός ελέγχου είναι σωστός.'),
                TextEntry::make('business.legal_name')->label('Υπάρχων πελάτης')->placeholder('Όχι ακόμα: μπορείτε να τον ανοίξετε')
                    ->url(fn (CallbackRequest $r) => $r->business ? BusinessResource::getUrl('edit', ['record' => $r->business]) : null),
                TextEntry::make('created_at')->label('Ζητήθηκε')->dateTime('d/m/Y H:i'),
                TextEntry::make('page')->label('Από τη σελίδα')->placeholder('—'),
            ]),
            Section::make('Χειρισμός')->columnSpanFull()->schema([
                ToggleButtons::make('status')->label('Κατάσταση')->options(CallbackRequest::STATUSES)->inline()->required()
                    ->colors(['new' => 'warning', 'contacted' => 'info', 'done' => 'success']),
                Textarea::make('notes')->label('Σημειώσεις')->rows(4)->maxLength(5000),
                TextEntry::make('handledBy.name')->label('Τελευταίος χειρισμός')->placeholder('—')
                    ->formatStateUsing(fn ($state, CallbackRequest $r) => $state.($r->handled_at ? ' · '.$r->handled_at->format('d/m/Y H:i') : '')),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->poll('60s')
            ->modifyQueryUsing(fn ($query) => $query->with('business'))
            ->columns([
                TextColumn::make('created_at')->label('Πότε')->dateTime('d/m H:i')->sortable()
                    ->description(fn (CallbackRequest $r) => $r->created_at->locale('el')->diffForHumans()),
                TextColumn::make('first_name')->label('Όνομα')->searchable(['first_name', 'last_name'])
                    ->formatStateUsing(fn ($state, CallbackRequest $r) => $r->fullName())->weight('bold'),
                TextColumn::make('mobile')->label('Κινητό')->searchable()->url(fn (CallbackRequest $r) => 'tel:+30'.$r->mobile),
                TextColumn::make('vat')->label('ΑΦΜ')->searchable()->description(fn (CallbackRequest $r) => $r->business?->legal_name),
                TextColumn::make('status')->label('Κατάσταση')->badge()->formatStateUsing(fn ($state) => CallbackRequest::STATUSES[$state] ?? $state)
                    ->color(fn ($state) => ['new' => 'warning', 'contacted' => 'info', 'done' => 'success'][$state] ?? 'gray'),
            ])
            ->filters([SelectFilter::make('status')->label('Κατάσταση')->options(CallbackRequest::STATUSES)])
            ->recordActions([
                Action::make('contacted')->label('Επικοινωνήσαμε')->icon('heroicon-o-check')->color('info')
                    ->visible(fn (CallbackRequest $r) => $r->status === 'new')
                    ->action(fn (CallbackRequest $r) => $r->forceFill(['status' => 'contacted', 'handled_by_id' => Filament::auth()->id(), 'handled_at' => now()])->save()),
                EditAction::make()->label('Άνοιγμα'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCallbackRequests::route('/'),
            'edit' => EditCallbackRequest::route('/{record}/edit'),
        ];
    }
}
