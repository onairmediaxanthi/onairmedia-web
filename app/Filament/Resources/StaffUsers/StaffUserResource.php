<?php

namespace App\Filament\Resources\StaffUsers;

use App\Enums\UserRole;
use App\Filament\Resources\StaffUsers\Pages\CreateStaffUser;
use App\Filament\Resources\StaffUsers\Pages\EditStaffUser;
use App\Filament\Resources\StaffUsers\Pages\ListStaffUsers;
use App\Models\User;
use App\Services\StaffAccounts;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;
use InvalidArgumentException;

/** Οι χρήστες της διαχείρισης (όχι οι πελάτες) */
class StaffUserResource extends Resource
{
    use \App\Filament\Concerns\RestrictedToArea;

    protected static string $area = 'users';

    protected static ?string $model = User::class;

    protected static ?string $slug = 'xristes';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static ?string $navigationLabel = 'Χρήστες διαχείρισης';

    protected static ?string $modelLabel = 'χρήστης διαχείρισης';

    protected static ?string $pluralModelLabel = 'Χρήστες διαχείρισης';

    protected static string|\UnitEnum|null $navigationGroup = 'Ρυθμίσεις';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('role', '!=', UserRole::Customer->value);
    }

    public static function rolesHelp(): HtmlString
    {
        $rows = collect(UserRole::staffOptions())->map(fn ($label, $value) => '<li><strong>'.e($label).':</strong> '
            .e(collect(UserRole::from($value)->areas())->map(fn ($a) => UserRole::AREAS[$a])->implode(' · ')).'</li>')->implode('');

        return new HtmlString('<ul style="margin-top:4px;display:grid;gap:2px">'.$rows.'</ul>');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Χρήστης')->columns(2)->columnSpanFull()->schema([
                TextInput::make('name')->label('Ονοματεπώνυμο')->required()->maxLength(120),
                TextInput::make('email')->label('Email')->email()->required()->maxLength(255)->unique(ignoreRecord: true),
                Select::make('role')->label('Ρόλος')->options(UserRole::staffOptions())->required()->native(false)
                    ->disabled(fn (?User $record) => $record && $record->is(Filament::auth()->user()))
                    ->helperText(self::rolesHelp())->columnSpanFull(),
                Toggle::make('send_invite')->label('Αποστολή πρόσκλησης τώρα')->default(true)->visibleOn('create')
                    ->helperText('Ο χρήστης λαμβάνει email για να ορίσει κωδικό. Στην πρώτη σύνδεση ρυθμίζει υποχρεωτικά 2FA.'),
            ]),
            Section::make('Ασφάλεια')->columns(4)->columnSpanFull()->visibleOn('edit')->schema([
                TextEntry::make('mfa')->label('2FA')->badge()
                    ->state(fn (User $record) => $record->app_authentication_secret ? 'Ενεργό' : 'Δεν έχει ρυθμιστεί')
                    ->color(fn (User $record) => $record->app_authentication_secret ? 'success' : 'warning'),
                TextEntry::make('last_login_at')->label('Τελευταία σύνδεση')->dateTime('d/m/Y H:i')->placeholder('Ποτέ'),
                TextEntry::make('created_at')->label('Δημιουργία')->dateTime('d/m/Y'),
                TextEntry::make('status')->label('Κατάσταση')->badge()
                    ->state(fn (User $record) => $record->disabled_at ? 'Απενεργοποιημένος από '.$record->disabled_at->format('d/m/Y') : 'Ενεργός')
                    ->color(fn (User $record) => $record->disabled_at ? 'danger' : 'success'),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')->label('Όνομα')->weight('bold')->searchable()->description(fn (User $r) => $r->email),
                TextColumn::make('role')->label('Ρόλος')->badge()->formatStateUsing(fn (UserRole $state) => $state->label())
                    ->color(fn (UserRole $state) => $state === UserRole::Admin ? 'primary' : 'gray'),
                IconColumn::make('mfa')->label('2FA')->boolean()->state(fn (User $r) => (bool) $r->app_authentication_secret),
                TextColumn::make('last_login_at')->label('Τελευταία σύνδεση')->dateTime('d/m/Y H:i')->placeholder('Ποτέ')->sortable(),
                IconColumn::make('active')->label('Ενεργός')->boolean()->state(fn (User $r) => $r->disabled_at === null),
            ])
            ->filters([
                SelectFilter::make('role')->label('Ρόλος')->options(UserRole::staffOptions()),
            ])
            ->recordActions([EditAction::make()]);
    }

    /** Κοινές ενέργειες (σελίδα επεξεργασίας) */
    public static function securityActions(): array
    {
        $fail = fn (InvalidArgumentException $e) => Notification::make()->danger()->title($e->getMessage())->send();

        return [
            Action::make('invite')->label('Νέα πρόσκληση')->icon('heroicon-o-envelope')->color('gray')
                ->requiresConfirmation()
                ->modalDescription(fn (User $record) => 'Θα σταλεί στο '.$record->email.' σύνδεσμος για να ορίσει (νέο) κωδικό, που ισχύει 24 ώρες.')
                ->hidden(fn (User $record) => $record->disabled_at !== null)
                ->action(function (User $record) {
                    app(StaffAccounts::class)->invite($record, Filament::auth()->user());
                    Notification::make()->success()->title('Η πρόσκληση στάλθηκε')->send();
                }),
            Action::make('reset_mfa')->label('Επαναφορά 2FA')->icon('heroicon-o-device-phone-mobile')->color('warning')
                ->requiresConfirmation()
                ->modalDescription('Π.χ. όταν χαθεί το κινητό. Στην επόμενη σύνδεση ο χρήστης θα ρυθμίσει ξανά το 2FA.')
                ->hidden(fn (User $record) => ! $record->app_authentication_secret || $record->is(Filament::auth()->user()))
                ->action(function (User $record) {
                    $record->forceFill(['app_authentication_secret' => null, 'app_authentication_recovery_codes' => null])->save();
                    Notification::make()->success()->title('Το 2FA επαναφέρθηκε')->send();
                }),
            Action::make('toggle')->label(fn (User $record) => $record->disabled_at ? 'Ενεργοποίηση' : 'Απενεργοποίηση')
                ->icon(fn (User $record) => $record->disabled_at ? 'heroicon-o-lock-open' : 'heroicon-o-lock-closed')
                ->color(fn (User $record) => $record->disabled_at ? 'success' : 'danger')
                ->requiresConfirmation()
                ->modalDescription(fn (User $record) => $record->disabled_at ? 'Ο χρήστης θα μπορεί ξανά να συνδεθεί.' : 'Ο χρήστης αποσυνδέεται αμέσως και δεν μπορεί να μπει. Οι προσφορές του μένουν.')
                ->hidden(fn (User $record) => $record->is(Filament::auth()->user()))
                ->action(function (User $record) use ($fail) {
                    try {
                        app(StaffAccounts::class)->guard($record, Filament::auth()->user(), disable: ! $record->disabled_at);
                    } catch (InvalidArgumentException $e) {
                        return $fail($e);
                    }
                    $record->forceFill(['disabled_at' => $record->disabled_at ? null : now(), 'remember_token' => null])->save();
                    Notification::make()->success()->title($record->disabled_at ? 'Ο χρήστης απενεργοποιήθηκε' : 'Ο χρήστης ενεργοποιήθηκε')->send();
                }),
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListStaffUsers::route('/'),
            'create' => CreateStaffUser::route('/create'),
            'edit' => EditStaffUser::route('/{record}/edit'),
        ];
    }
}
