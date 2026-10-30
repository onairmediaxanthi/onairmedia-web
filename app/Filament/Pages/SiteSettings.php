<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class SiteSettings extends Page
{
    use \App\Filament\Concerns\PageRestrictedToArea;

    protected static string $area = 'site';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static ?string $navigationLabel = 'Ρυθμίσεις site';

    protected static ?string $title = 'Ρυθμίσεις site';

    protected static string|\UnitEnum|null $navigationGroup = 'Αρχική σελίδα';

    protected static ?int $navigationSort = 90;

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill(array_diff_key(array_intersect_key(Setting::allValues(), Setting::DEFAULTS), array_flip([...ExtraCharges::KEYS, ...QuoteSettings::KEYS])));
    }

    public function form(Schema $schema): Schema
    {
        return $schema->statePath('data')->components([
            Section::make('Λογότυπο')->columns(3)->schema([
                FileUpload::make('logo_light')->label('Λογότυπο (φωτεινή εμφάνιση)')
                    ->disk('public')->directory('brand')->visibility('public')
                    ->image()->maxSize(2048)
                    ->acceptedFileTypes(['image/webp', 'image/png', 'image/svg+xml'])
                    ->helperText('Οριζόντιο, με διάφανο φόντο.'),
                FileUpload::make('logo_dark')->label('Λογότυπο (σκοτεινή εμφάνιση)')
                    ->disk('public')->directory('brand')->visibility('public')
                    ->image()->maxSize(2048)
                    ->acceptedFileTypes(['image/webp', 'image/png', 'image/svg+xml'])
                    ->helperText('Ανοιχτόχρωμη εκδοχή. Κενό = χρησιμοποιείται το ίδιο.'),
                FileUpload::make('favicon')->label('Εικονίδιο καρτέλας (favicon)')
                    ->disk('public')->directory('brand')->visibility('public')
                    ->image()->maxSize(512)
                    ->acceptedFileTypes(['image/png', 'image/svg+xml'])
                    ->helperText('Τετράγωνο PNG, 512×512.'),
            ]),
            Section::make('Λογότυπα μέσων')->columns(2)
                ->description('Εμφανίζονται στις προτάσεις (PDF και email). Τα λογότυπα των σταθμών μπαίνουν από την καρτέλα κάθε σταθμού.')
                ->schema([
                    FileUpload::make('logo_thrakitoday')->label('Λογότυπο thrakitoday.gr')
                        ->disk('public')->directory('brand')->visibility('public')->image()->maxSize(2048)
                        ->acceptedFileTypes(['image/webp', 'image/png', 'image/jpeg']),
                    FileUpload::make('logo_led')->label('Λογότυπο ή φωτογραφία πινακίδας LED')
                        ->disk('public')->directory('brand')->visibility('public')->image()->maxSize(4096)
                        ->acceptedFileTypes(['image/webp', 'image/png', 'image/jpeg']),
                ]),
            Section::make('Στοιχεία εταιρείας')->columns(2)->schema([
                TextInput::make('company_name')->label('Επωνυμία')->required()->maxLength(120),
                TextInput::make('phone')->label('Τηλέφωνο')->tel()->maxLength(30),
                TextInput::make('email')->label('Email επικοινωνίας')->email()->maxLength(120),
                TextInput::make('address')->label('Διεύθυνση')->maxLength(200),
                TextInput::make('vat')->label('ΑΦΜ')->maxLength(12),
                TextInput::make('gemi')->label('Αρ. ΓΕΜΗ')->maxLength(20),
                Textarea::make('footer_text')->label('Κείμενο footer')->rows(2)->maxLength(300)->columnSpanFull(),
            ]),
            Section::make('Social media')->schema([
                KeyValue::make('socials')->label('')->keyLabel('Δίκτυο')->valueLabel('Link')
                    ->keyPlaceholder('facebook')->valuePlaceholder('https://...'),
            ]),
            Section::make('Τι παίζουν οι σταθμοί & Top 40')->columns(3)->collapsible()
                ->description('Κάθε λεπτό γράφεται το τραγούδι που παίζει κάθε σταθμός. Μόνιμο μένει μόνο το Top 40 κάθε μήνα· οι μεταδόσεις σβήνονται αυτόματα κάθε βράδυ, όπως ορίζετε εδώ.')
                ->schema([
                    \Filament\Forms\Components\Toggle::make('airplay_enabled')->label('Καταγραφή ενεργή')->inline(false),
                    TextInput::make('chart_size')->label('Μέγεθος λίστας')->numeric()->integer()->minValue(5)->maxValue(100)->suffix('θέσεις')->required(),
                    TextInput::make('chart_min_plays')->label('Ελάχιστες μεταδόσεις')->numeric()->integer()->minValue(1)->maxValue(100)->required()
                        ->helperText('Τόσες τουλάχιστον μέσα στην εβδομάδα για να μπει στη λίστα.'),
                    TextInput::make('airplay_keep_days')->label('Αυτόματη διαγραφή μεταδόσεων μετά από')->numeric()->integer()->minValue(1)->maxValue(730)->suffix('ημέρες')->required()
                        ->helperText('Κάθε μετάδοση με ώρα (για «τι έπαιξε» και εξαγωγή CSV). Περίπου 1 MB ανά 10 ημέρες για 12 σταθμούς. Τα μηνιαία Top 40 μένουν, μέχρι να τα σβήσετε εσείς από το «Top 40».'),
                    \Filament\Forms\Components\Toggle::make('airplay_require_artist')->label('Μόνο «Καλλιτέχνης - Τίτλος»')->inline(false)
                        ->helperText('Ό,τι έρχεται χωρίς καλλιτέχνη (συνήθως όνομα εκπομπής ή jingle) δεν γράφεται.'),
                    \Filament\Forms\Components\TagsInput::make('airplay_ignore')->label('Λέξεις που σημαίνουν «όχι τραγούδι»')->columnSpan(2)
                        ->placeholder('π.χ. διαφήμιση')->helperText('Αν ο τίτλος περιέχει μία από αυτές (ως λέξη, χωρίς τόνους/κεφαλαία), δεν γράφεται.'),
                ]),
        ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('save')
                ->footer([
                    Actions::make([
                        Action::make('save')->label('Αποθήκευση')->submit('save')->keyBindings(['mod+s']),
                    ]),
                ]),
        ]);
    }

    public function save(): void
    {
        Setting::putMany($this->form->getState());

        Notification::make()->success()->title('Οι ρυθμίσεις αποθηκεύτηκαν')->send();
    }
}
