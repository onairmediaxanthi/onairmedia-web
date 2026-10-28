<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

/** Τιμοκατάλογος: δημοσιευμένα άρθρα, δημιουργικό και ΦΠΑ */
class ExtraCharges extends Page
{
    use \App\Filament\Concerns\PageRestrictedToArea;

    protected static string $area = 'pricing';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCurrencyEuro;

    protected static ?string $navigationLabel = 'Λοιπές χρεώσεις';

    protected static ?string $title = 'Λοιπές χρεώσεις';

    protected static ?string $slug = 'timokatalogos/loipes-xreoseis';

    protected static string|\UnitEnum|null $navigationGroup = 'Τιμοκατάλογος';

    protected static ?int $navigationSort = 40;

    public const KEYS = [
        'vat_rate', 'price_sponsored_article', 'price_article_writing', 'price_spot_production',
        'price_spot_production_voices', 'price_led_graphic', 'price_banner_design',
    ];

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill(array_intersect_key(Setting::allValues(), array_flip(self::KEYS)));
    }

    public function form(Schema $schema): Schema
    {
        $money = fn (string $name, string $label, ?string $help = null) => TextInput::make($name)->label($label)
            ->numeric()->minValue(0)->maxValue(100000)->step(0.01)->suffix('€')->helperText($help);

        return $schema->statePath('data')->components([
            Section::make('thrakitoday.gr')->columns(2)->description('Χωρίς ΦΠΑ.')->schema([
                $money('price_sponsored_article', 'Δημοσίευση χορηγούμενου άρθρου', 'Με το κείμενο του πελάτη.'),
                $money('price_article_writing', 'Συγγραφή άρθρου από εμάς', 'Επιπλέον της δημοσίευσης.'),
            ]),
            Section::make('Δημιουργικό')->columns(2)->description('Για πελάτες που δεν έχουν έτοιμο υλικό. Χωρίς ΦΠΑ.')->schema([
                $money('price_spot_production', 'Παραγωγή ραδιοφωνικού σποτ (1 φωνή)'),
                $money('price_spot_production_voices', 'Παραγωγή σποτ με διάλογο (2+ φωνές)'),
                $money('price_led_graphic', 'Γραφικό για πινακίδα LED'),
                $money('price_banner_design', 'Σχεδιασμός banner (όλες οι διαστάσεις)'),
            ]),
            Section::make('ΦΠΑ')->schema([
                TextInput::make('vat_rate')->label('Συντελεστής ΦΠΑ')->numeric()->minValue(0)->maxValue(30)->suffix('%')->required()
                    ->helperText('Όλες οι τιμές του τιμοκαταλόγου καταχωρίζονται χωρίς ΦΠΑ· ο πελάτης βλέπει και το τελικό ποσό.'),
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
        Setting::putMany(array_intersect_key($this->form->getState(), array_flip(self::KEYS)));

        Notification::make()->success()->title('Οι χρεώσεις αποθηκεύτηκαν')->send();
    }
}
