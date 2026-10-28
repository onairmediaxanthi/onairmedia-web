<?php

namespace App\Filament\Resources\Campaigns;

use App\Filament\Resources\Campaigns\Pages\CreateCampaign;
use App\Filament\Resources\Campaigns\Pages\EditCampaign;
use App\Filament\Resources\Campaigns\Pages\ListCampaigns;
use App\Models\Business;
use App\Models\Campaign;
use App\Models\Coupon;
use App\Models\Package;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;

class CampaignResource extends Resource
{
    use \App\Filament\Concerns\RestrictedToArea;

    protected static string $area = 'sales';

    protected static ?string $model = Campaign::class;

    protected static ?string $slug = 'apostoles';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMegaphone;

    protected static ?string $navigationLabel = 'Μαζικές αποστολές';

    protected static ?string $modelLabel = 'αποστολή';

    protected static ?string $pluralModelLabel = 'Μαζικές αποστολές προσφορών';

    protected static string|\UnitEnum|null $navigationGroup = 'Πωλήσεις';

    protected static ?int $navigationSort = 25;

    /** Πόσοι θα το λάβουν, με τις τρέχουσες επιλογές */
    public static function audienceCount(Get $get): int
    {
        $c = new Campaign(['audience' => ['mode' => $get('audience.mode'), 'types' => $get('audience.types'), 'business_ids' => $get('audience.business_ids')]]);

        return $c->audienceQuery()->count();
    }

    public static function form(Schema $schema): Schema
    {
        $locked = fn (?Campaign $record) => $record && ! $record->isEditable();

        return $schema->components([
            Section::make('Το email')->columns(2)->columnSpanFull()->disabled($locked)->schema([
                TextInput::make('name')->label('Όνομα (μόνο για εσάς)')->required()->maxLength(120)->placeholder('π.χ. Black Friday 2026'),
                TextInput::make('subject')->label('Θέμα email')->required()->maxLength(150),
                TextInput::make('preheader')->label('Σύντομο κείμενο προεπισκόπησης')->maxLength(200)->columnSpanFull()
                    ->helperText('Φαίνεται δίπλα στο θέμα στα εισερχόμενα.'),
                RichEditor::make('body')->label('Κείμενο')->columnSpanFull()
                    ->toolbarButtons(['bold', 'italic', 'link', 'h2', 'h3', 'bulletList', 'orderedList', 'undo', 'redo'])
                    ->helperText('Το «Καλησπέρα [όνομα]» μπαίνει αυτόματα στην αρχή.'),
                Select::make('package_ids')->label('Προσφορές/πακέτα που προβάλλει')->multiple()->columnSpanFull()
                    ->options(fn () => Package::query()->available()->pluck('name', 'id'))
                    ->helperText('Μπαίνουν ως κάρτες με παροχές, τιμή και κουμπί «Το θέλω». Οι παραλήπτες είναι εγγεγραμμένοι πελάτες, άρα βλέπουν τιμές.'),
                Select::make('coupon_id')->label('Κουπόνι στο email (προαιρετικό)')
                    ->options(fn () => Coupon::query()->where('is_active', true)->whereNull('user_id')
                        ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>', now()))->get()
                        ->mapWithKeys(fn (Coupon $c) => [$c->id => $c->code.' · '.$c->name.' · '.$c->valueLabel()])->all()),
                TextInput::make('cta_label')->label('Κουμπί')->maxLength(40)->placeholder('Βγάλτε προσφορά'),
                TextInput::make('cta_url')->label('Link κουμπιού')->maxLength(255)->rule('regex:/^(\/(?!\/)|https:\/\/)/')
                    ->placeholder('κενό = Studio Καμπάνιας'),
            ]),

            Section::make('Σε ποιους')->columnSpanFull()->disabled($locked)
                ->description(new HtmlString('Μόνο πελάτες που έχουν δώσει <strong>ρητή συγκατάθεση</strong> για προσφορές και έχουν ενεργό λογαριασμό. Όσοι δεν έχουν συγκατάθεση δεν εμφανίζονται καν εδώ.'))
                ->schema([
                    ToggleButtons::make('audience.mode')->label('Κοινό')->inline()->default('all')->live()->required()
                        ->options(['all' => 'Όλοι όσοι δέχονται', 'types' => 'Ανά είδος επιχείρησης', 'selected' => 'Επιλεγμένοι πελάτες']),
                    CheckboxList::make('audience.types')->label('Είδη επιχείρησης')->options(Business::TYPES)->columns(3)->live()
                        ->visible(fn (Get $get) => $get('audience.mode') === 'types'),
                    Select::make('audience.business_ids')->label('Πελάτες')->multiple()->searchable()->live()
                        ->options(fn () => Campaign::consenting()->orderBy('legal_name')->get()->mapWithKeys(fn (Business $b) => [$b->id => $b->legal_name.' · '.$b->city])->all())
                        ->visible(fn (Get $get) => $get('audience.mode') === 'selected'),
                    TextEntry::make('audience_count')->label('Θα το λάβουν')->badge()->color('primary')
                        ->state(fn (Get $get) => self::audienceCount($get).' πελάτες'),
                    DateTimePicker::make('scheduled_at')->label('Αποστολή στις (προαιρετικό)')->seconds(false)->native(false)->minDate(now())
                        ->helperText('Κενό = μόλις πατήσετε «Αποστολή».'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(fn ($q) => $q->withCount([
                'recipients', 'recipients as sent_count' => fn ($r) => $r->where('status', 'sent'),
                'recipients as unsub_count' => fn ($r) => $r->whereNotNull('unsubscribed_at'),
            ]))
            ->columns([
                TextColumn::make('name')->label('Αποστολή')->weight('bold')->searchable()->description(fn (Campaign $r) => $r->subject),
                TextColumn::make('status')->label('Κατάσταση')->badge()->formatStateUsing(fn ($state) => Campaign::STATUSES[$state] ?? $state)
                    ->color(fn ($state) => match ($state) { 'sent' => 'success', 'sending', 'scheduled' => 'warning', 'cancelled' => 'danger', default => 'gray' }),
                TextColumn::make('sent_count')->label('Στάλθηκαν')->formatStateUsing(fn ($state, Campaign $r) => $state.' / '.$r->recipients_count),
                TextColumn::make('unsub_count')->label('Διαγραφές')->placeholder('0'),
                TextColumn::make('scheduled_at')->label('Προγραμματισμός')->dateTime('d/m/Y H:i')->placeholder('—'),
                TextColumn::make('finished_at')->label('Ολοκληρώθηκε')->dateTime('d/m/Y H:i')->placeholder('—'),
            ])
            ->recordActions([EditAction::make()->label(fn (Campaign $r) => $r->isEditable() ? 'Επεξεργασία' : 'Προβολή')]);
    }

    public static function getRelations(): array
    {
        return [RelationManagers\RecipientsRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCampaigns::route('/'),
            'create' => CreateCampaign::route('/create'),
            'edit' => EditCampaign::route('/{record}/edit'),
        ];
    }
}
