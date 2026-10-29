<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

/** Ισχύς προσφορών, υπενθυμίσεις πελατών και ειδοποιήσεις προς την εταιρεία */
class QuoteSettings extends Page
{
    use \App\Filament\Concerns\PageRestrictedToArea;

    protected static string $area = 'settings';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAdjustmentsHorizontal;

    protected static ?string $navigationLabel = 'Ρυθμίσεις προσφορών';

    protected static ?string $title = 'Ρυθμίσεις προσφορών & ειδοποιήσεων';

    protected static ?string $slug = 'rythmiseis-prosforon';

    protected static string|\UnitEnum|null $navigationGroup = 'Ρυθμίσεις';

    protected static ?int $navigationSort = 90;

    public const KEYS = [
        'quote_valid_days', 'quote_reminder_days', 'quote_reminder_max', 'notify_emails',
        'welcome_coupon_enabled', 'welcome_coupon_type', 'welcome_coupon_value', 'welcome_coupon_days', 'welcome_coupon_min', 'welcome_coupon_prefix',
        'campaign_per_minute', 'max_staff_discount', 'callback_emails', 'callback_floating', 'notify_registered', 'notify_quote_saved', 'notify_quote_changed', 'notify_quote_submitted', 'notify_quote_deleted', 'notify_quote_pdf', 'notify_wizard_used',
    ];

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill(array_intersect_key(Setting::allValues(), array_flip(self::KEYS)));
    }

    public function form(Schema $schema): Schema
    {
        return $schema->statePath('data')->components([
            Section::make('Προσφορές πελατών')->columns(3)->schema([
                TextInput::make('quote_valid_days')->label('Ισχύς προσφοράς')->numeric()->integer()->minValue(1)->maxValue(90)->suffix('ημέρες')->required()
                    ->helperText('Για τόσες μέρες «κλειδώνουν» οι τιμές. Μετά ο πελάτης την ανανεώνει με τις τρέχουσες.'),
                TextInput::make('quote_reminder_days')->label('Υπενθύμιση κάθε')->numeric()->integer()->minValue(0)->maxValue(60)->suffix('ημέρες')->required()
                    ->helperText('0 = καμία υπενθύμιση. Στέλνεται στις 10:00 το πρωί.'),
                TextInput::make('max_staff_discount')->label('Μέγιστη χειροκίνητη έκπτωση')->numeric()->minValue(0)->maxValue(90)->suffix('%')->required()
                    ->helperText('Πόση έκπτωση μπορεί να δώσει το προσωπικό σε μια προσφορά.'),
                TextInput::make('quote_reminder_max')->label('Το πολύ')->numeric()->integer()->minValue(0)->maxValue(10)->suffix('υπενθυμίσεις')->required()
                    ->helperText('Ανά προσφορά. Ο πελάτης μπορεί να τις σταματήσει από το email.'),
            ]),
            Section::make('Κουπόνι καλωσορίσματος')->columns(3)
                ->description('Κάθε νέος πελάτης παίρνει με email προσωπικό κωδικό μίας χρήσης, μόλις επιβεβαιώσει το email του (ή ενεργοποιήσει λογαριασμό που ανοίξατε εσείς). Ένας ανά λογαριασμό, άρα ένας ανά ΑΦΜ.')
                ->schema([
                    Toggle::make('welcome_coupon_enabled')->label('Ενεργό')->inline(false)->live()->columnSpanFull(),
                    \Filament\Forms\Components\Select::make('welcome_coupon_type')->label('Είδος')->options(\App\Models\Coupon::TYPES)->native(false)->required(),
                    TextInput::make('welcome_coupon_value')->label('Αξία')->numeric()->minValue(0)->maxValue(100000)->required(),
                    TextInput::make('welcome_coupon_days')->label('Ισχύει για')->numeric()->integer()->minValue(1)->maxValue(365)->suffix('ημέρες')->required(),
                    TextInput::make('welcome_coupon_min')->label('Ελάχιστη καμπάνια')->numeric()->minValue(0)->suffix('€')->helperText('Χωρίς ΦΠΑ. Κενό = χωρίς όριο.'),
                    TextInput::make('welcome_coupon_prefix')->label('Πρόθεμα κωδικού')->maxLength(12)->alphaNum()->helperText('π.χ. KALOS → KALOS-7FK2QM'),
                ]),
            Section::make('«Να σας καλέσουμε»')->columns(2)->schema([
                TextInput::make('callback_emails')->label('Email για αιτήματα επικοινωνίας')->maxLength(500)
                    ->placeholder('sales@onairmedia.gr')
                    ->helperText('Ένα ή περισσότερα, χωρισμένα με κόμμα. Κενό = τα email ειδοποιήσεων από κάτω.')
                    ->rule(fn () => function (string $attribute, $value, \Closure $fail) {
                        foreach (array_filter(array_map('trim', preg_split('/[,;\s]+/', (string) $value))) as $e) {
                            if (! filter_var($e, FILTER_VALIDATE_EMAIL)) {
                                $fail('Μη έγκυρο email: '.$e);
                            }
                        }
                    }),
                Toggle::make('callback_floating')->label('Σταθερό κουμπί «Να σας καλέσουμε;» σε όλες τις σελίδες')->inline(false),
            ]),
            Section::make('Μαζικές αποστολές')->schema([
                TextInput::make('campaign_per_minute')->label('Email ανά λεπτό')->numeric()->integer()->minValue(1)->maxValue(200)->required()
                    ->helperText('Οι πάροχοι φιλοξενίας έχουν όριο ανά ώρα (συχνά 300–500). Με 20/λεπτό στέλνονται ~1.200 την ώρα· ρωτήστε τον πάροχο αν χρειάζεται λιγότερο.'),
            ]),
            Section::make('Ειδοποιήσεις προς εσάς')
                ->description('Όλες οι ενέργειες φαίνονται πάντα στη «Δραστηριότητα πελατών» και οι σημαντικές στο καμπανάκι πάνω δεξιά. Εδώ διαλέγετε ποιες έρχονται και με email.')
                ->columns(3)->schema([
                    TextInput::make('notify_emails')->label('Email για ειδοποιήσεις')->columnSpanFull()->maxLength(500)
                        ->placeholder('sales@onairmedia.gr, info@onairmedia.gr')
                        ->helperText('Ένα ή περισσότερα, χωρισμένα με κόμμα. Κενό = το email επικοινωνίας των ρυθμίσεων site.')
                        ->rule(fn () => function (string $attribute, $value, \Closure $fail) {
                            foreach (array_filter(array_map('trim', preg_split('/[,;\s]+/', (string) $value))) as $e) {
                                if (! filter_var($e, FILTER_VALIDATE_EMAIL)) {
                                    $fail('Μη έγκυρο email: '.$e);
                                }
                            }
                        }),
                    Toggle::make('notify_registered')->label('Νέα εγγραφή'),
                    Toggle::make('notify_quote_saved')->label('Νέα προσφορά'),
                    Toggle::make('notify_quote_changed')->label('Αλλαγή προσφοράς από πελάτη'),
                    Toggle::make('notify_quote_submitted')->label('Παραγγελία (ταμείο)'),
                    Toggle::make('notify_quote_deleted')->label('Διαγραφή προσφοράς'),
                    Toggle::make('notify_quote_pdf')->label('Λήψη PDF'),
                    Toggle::make('notify_wizard_used')->label('Είδε προτάσεις στον οδηγό'),
                ]),
        ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('save')
                ->footer([Actions::make([Action::make('save')->label('Αποθήκευση')->submit('save')->keyBindings(['mod+s'])])]),
        ]);
    }

    public function save(): void
    {
        Setting::putMany(array_intersect_key($this->form->getState(), array_flip(self::KEYS)));
        Notification::make()->success()->title('Οι ρυθμίσεις αποθηκεύτηκαν')->send();
    }
}
