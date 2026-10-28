<?php

namespace App\Filament\Resources\Coupons;

use App\Filament\Resources\Coupons\Pages\CreateCoupon;
use App\Filament\Resources\Coupons\Pages\EditCoupon;
use App\Filament\Resources\Coupons\Pages\ListCoupons;
use App\Models\Coupon;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CouponResource extends Resource
{
    use \App\Filament\Concerns\RestrictedToArea;

    protected static string $area = 'sales';

    protected static ?string $model = Coupon::class;

    protected static ?string $slug = 'kouponia';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTicket;

    protected static ?string $navigationLabel = 'Κουπόνια';

    protected static ?string $modelLabel = 'κουπόνι';

    protected static ?string $pluralModelLabel = 'Κουπόνια';

    protected static string|\UnitEnum|null $navigationGroup = 'Πωλήσεις';

    protected static ?int $navigationSort = 20;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Κουπόνι')->columns(2)->columnSpanFull()->schema([
                TextInput::make('code')->label('Κωδικός')->required()->maxLength(40)->unique(ignoreRecord: true)
                    ->rule('regex:/^[A-Za-z0-9-]+$/')->validationMessages(['regex' => 'Μόνο λατινικά γράμματα, αριθμοί και παύλα.'])
                    ->helperText('π.χ. BLACKFRIDAY. Ο πελάτης τον γράφει στο ταμείο, χωρίς διάκριση πεζών/κεφαλαίων.')
                    ->suffixAction(Action::make('generate')->icon('heroicon-o-sparkles')->tooltip('Τυχαίος κωδικός')
                        ->action(fn (Set $set) => $set('code', Coupon::generateCode()))),
                TextInput::make('name')->label('Όνομα καμπάνιας')->required()->maxLength(120)->placeholder('π.χ. Black Friday 2026'),
                TextInput::make('description')->label('Περιγραφή για τον πελάτη')->maxLength(300)->columnSpanFull(),
            ]),
            Section::make('Έκπτωση')->columns(4)->columnSpanFull()->description('Αφαιρείται πριν τον ΦΠΑ.')->schema([
                Select::make('type')->label('Είδος')->options(Coupon::TYPES)->default('percent')->required()->live()->native(false),
                TextInput::make('value')->label('Αξία')->numeric()->required()->minValue(0.01)
                    ->maxValue(fn (Get $get) => $get('type') === 'percent' ? 90 : 100000)
                    ->suffix(fn (Get $get) => $get('type') === 'percent' ? '%' : '€'),
                TextInput::make('min_subtotal')->label('Ελάχιστη καμπάνια')->numeric()->minValue(0)->suffix('€')->helperText('Χωρίς ΦΠΑ.'),
                TextInput::make('max_discount')->label('Μέγιστη έκπτωση')->numeric()->minValue(0)->suffix('€')
                    ->visible(fn (Get $get) => $get('type') === 'percent'),
                Toggle::make('applies_to_packages')->label('Ισχύει και σε έτοιμα πακέτα/προσφορές')->inline(false)->columnSpan(2)
                    ->helperText('Συνήθως όχι: οι προσφορές έχουν ήδη έκπτωση.'),
            ]),
            Section::make('Διάρκεια & όρια')->columns(4)->columnSpanFull()->schema([
                DateTimePicker::make('starts_at')->label('Από')->seconds(false)->native(false),
                DateTimePicker::make('ends_at')->label('Έως')->seconds(false)->native(false)->after('starts_at'),
                TextInput::make('usage_limit')->label('Συνολικές χρήσεις')->numeric()->integer()->minValue(1)->helperText('Κενό = απεριόριστες.'),
                TextInput::make('per_customer_limit')->label('Χρήσεις ανά πελάτη')->numeric()->integer()->minValue(1)->default(1)->required(),
                Toggle::make('is_active')->label('Ενεργό')->default(true)->inline(false),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(fn ($query) => $query->withCount('redemptions')->withSum('redemptions', 'amount')->with('user.business'))
            ->columns([
                TextColumn::make('code')->label('Κωδικός')->fontFamily('mono')->weight('bold')->searchable()->copyable(),
                TextColumn::make('name')->label('Όνομα')->searchable()
                    ->description(fn (Coupon $r) => $r->user ? 'Για: '.($r->user->business?->displayName() ?? $r->user->email) : null),
                TextColumn::make('value')->label('Έκπτωση')->state(fn (Coupon $r) => $r->valueLabel()),
                TextColumn::make('source')->label('Είδος')->badge()->formatStateUsing(fn ($state) => Coupon::SOURCES[$state] ?? $state)
                    ->color(fn ($state) => $state === 'welcome' ? 'success' : 'primary'),
                TextColumn::make('redemptions_count')->label('Χρήσεις')
                    ->formatStateUsing(fn ($state, Coupon $r) => $state.($r->usage_limit ? ' / '.$r->usage_limit : ''))
                    ->description(fn (Coupon $r) => $r->redemptions_sum_amount ? number_format((float) $r->redemptions_sum_amount, 2, ',', '.').' € έκπτωση' : null),
                TextColumn::make('ends_at')->label('Λήξη')->dateTime('d/m/Y')->placeholder('—')->sortable(),
                IconColumn::make('is_active')->label('Ενεργό')->boolean(),
            ])
            ->filters([
                SelectFilter::make('source')->label('Είδος')->options(Coupon::SOURCES),
            ])
            ->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCoupons::route('/'),
            'create' => CreateCoupon::route('/create'),
            'edit' => EditCoupon::route('/{record}/edit'),
        ];
    }
}
