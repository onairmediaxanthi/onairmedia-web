<?php

namespace App\Filament\Resources\Quotes;

use App\Filament\Resources\Quotes\Pages\CreateQuote;
use App\Filament\Resources\Quotes\Pages\ListQuotes;
use App\Filament\Resources\Quotes\Pages\ViewQuote;
use App\Models\Quote;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class QuoteResource extends Resource
{
    protected static ?string $model = Quote::class;

    protected static ?string $slug = 'prosfores';

    /**
     * Οι σύνδεσμοι χρησιμοποιούν τον κωδικό (ulid) της προσφοράς. Παλιά email με αριθμητικό id
     * ανοίγουν ακόμα, αλλά μόνο με αυστηρή αντιστοίχιση: ποτέ μετατροπή κειμένου σε αριθμό.
     */
    public static function resolveRecordRouteBinding(int|string $key, ?\Closure $modifyQuery = null): ?Model
    {
        $query = static::getRecordRouteBindingEloquentQuery();
        if ($modifyQuery) {
            $query = $modifyQuery($query) ?? $query;
        }
        $key = (string) $key;

        return ctype_digit($key) ? $query->whereKey((int) $key)->first() : $query->where('ulid', $key)->first();
    }

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?string $navigationLabel = 'Προσφορές πελατών';

    protected static ?string $modelLabel = 'προσφορά';

    protected static ?string $pluralModelLabel = 'Προσφορές πελατών';

    protected static string|\UnitEnum|null $navigationGroup = 'Πωλήσεις';

    protected static ?int $navigationSort = 5;

    protected static ?string $recordTitleAttribute = 'number';

    public static function getNavigationBadge(): ?string
    {
        $n = Quote::query()->where('status', 'submitted')->count();

        return $n ? (string) $n : null;
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Περιμένουν επιβεβαίωση';
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['user.business', 'createdBy'])->withoutGlobalScopes([SoftDeletingScope::class]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->columns(3)->components([
            Section::make('Μήνυμα πρότασης')->columnSpan(2)->visible(fn (Quote $r) => filled($r->intro))->schema([
                TextEntry::make('intro')->hiddenLabel()->formatStateUsing(fn ($state) => new \Illuminate\Support\HtmlString(nl2br(e($state)))),
            ]),
            Section::make('Γραμμές')->columnSpan(2)->schema([
                ViewEntry::make('items')->hiddenLabel()->view('filament.quote-items'),
            ]),
            Section::make('Στοιχεία')->columnSpan(1)->schema([
                TextEntry::make('title')->label('Τίτλος')->weight('bold'),
                TextEntry::make('status')->label('Κατάσταση')->badge()
                    ->formatStateUsing(fn ($state, Quote $record) => $record->trashed() ? 'Διαγράφηκε από τον πελάτη' : $record->statusLabel())
                    ->color(fn ($state, Quote $record) => $record->trashed() ? 'danger' : match ($state) { 'submitted' => 'warning', 'ordered' => 'success', default => 'gray' }),
                TextEntry::make('user.business.legal_name')->label('Πελάτης')
                    ->url(fn (Quote $r) => $r->user?->business ? url('/admin/businesses/'.$r->user->business->id.'/edit') : null),
                TextEntry::make('user.business.contact_person')->label('Επικοινωνία')
                    ->formatStateUsing(fn ($state, Quote $r) => $state.' · '.$r->user?->business?->mobile),
                TextEntry::make('user.email')->label('Email')->copyable(),
                TextEntry::make('source')->label('Πώς φτιάχτηκε')->formatStateUsing(fn ($state) => Quote::SOURCES[$state] ?? $state),
                TextEntry::make('starts_on')->label('Έναρξη')->date('d/m/Y')->placeholder('Χωρίς ημερομηνία'),
                TextEntry::make('days')->label('Ημέρες'),
                TextEntry::make('valid_until')->label('Ισχύει έως')->date('d/m/Y'),
                TextEntry::make('createdBy.name')->label('Ετοίμασε')->placeholder('Ο ίδιος ο πελάτης'),
                TextEntry::make('sent_at')->label('Στάλθηκε στον πελάτη')->dateTime('d/m/Y H:i')->placeholder('Όχι ακόμα')
                    ->formatStateUsing(fn ($state, Quote $r) => $state?->format('d/m/Y H:i').($r->sent_count > 1 ? ' ('.$r->sent_count.' φορές)' : '')),
                TextEntry::make('submitted_at')->label('Παραγγελία')->dateTime('d/m/Y H:i')->placeholder('—'),
                TextEntry::make('reminders_sent')->label('Υπενθυμίσεις')
                    ->formatStateUsing(fn ($state, Quote $r) => $state.($r->reminders_off ? ' · τις σταμάτησε' : '')),
                TextEntry::make('customer_notes')->label('Σημειώσεις πελάτη')->placeholder('—'),
                TextEntry::make('admin_notes')->label('Εσωτερικές σημειώσεις')->placeholder('—'),
            ]),
            Section::make('Απαντήσεις στον οδηγό')->columnSpanFull()->collapsed()
                ->visible(fn (Quote $r) => ! empty($r->answers['wizard']))
                ->schema([ViewEntry::make('answers')->hiddenLabel()->view('filament.quote-answers')]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('number')->label('Αριθμός')->searchable()->fontFamily('mono'),
                TextColumn::make('user.business.legal_name')->label('Πελάτης')->searchable()->limit(30)
                    ->description(fn (Quote $r) => $r->title),
                TextColumn::make('total')->label('Σύνολο')->money('EUR', locale: 'el')->sortable(),
                TextColumn::make('status')->label('Κατάσταση')->badge()
                    ->formatStateUsing(fn ($state, Quote $record) => $record->trashed() ? 'Διαγράφηκε' : $record->statusLabel())
                    ->color(fn ($state, Quote $record) => $record->trashed() ? 'danger' : ($record->isExpired() ? 'gray' : match ($state) { 'submitted' => 'warning', 'ordered' => 'success', default => 'info' })),
                TextColumn::make('source')->label('Πηγή')->formatStateUsing(fn ($state) => Quote::SOURCES[$state] ?? $state)->toggleable()
                    ->badge()->color(fn ($state) => $state === 'staff' ? 'warning' : 'gray')
                    ->description(fn (Quote $r) => $r->createdBy?->name),
                TextColumn::make('sent_at')->label('Στάλθηκε')->dateTime('d/m H:i')->placeholder('—')->toggleable(),
                TextColumn::make('created_at')->label('Δημιουργία')->dateTime('d/m/Y H:i')->sortable(),
                TextColumn::make('valid_until')->label('Λήξη')->date('d/m/Y')->sortable()->toggleable(),
                TextColumn::make('reminders_sent')->label('Υπενθ.')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')->label('Κατάσταση')->options(Quote::STATUSES),
                SelectFilter::make('source')->label('Πηγή')->options(Quote::SOURCES),
                TrashedFilter::make()->label('Διαγραμμένες')
                    ->placeholder('Χωρίς τις διαγραμμένες')->trueLabel('Μαζί με τις διαγραμμένες')->falseLabel('Μόνο διαγραμμένες'),
            ])
            ->recordActions([ViewAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListQuotes::route('/'),
            'create' => CreateQuote::route('/create'),
            'view' => ViewQuote::route('/{record}'),
        ];
    }
}
