<?php

namespace App\Filament\Resources\HomeSections\Schemas;

use App\Models\HomeSection;
use App\Models\Package;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class HomeSectionForm
{
    public const ICONS = [
        'radio' => 'Ραδιόφωνο', 'news' => 'Εφημερίδα', 'screen' => 'Οθόνη', 'chart' => 'Γράφημα', 'mic' => 'Μικρόφωνο',
        'megaphone' => 'Μεγάφωνο', 'users' => 'Κοινό', 'star' => 'Αστέρι', 'phone' => 'Κινητό', 'map' => 'Πόλη',
    ];

    public static function configure(Schema $schema): Schema
    {
        $is = fn (string ...$types) => fn (Get $get) => in_array($get('type'), $types, true);

        return $schema->components([
            Section::make('Ενότητα')->columns(2)->columnSpanFull()->schema([
                Select::make('type')->label('Τύπος')->options(HomeSection::TYPES)->required()->live()
                    ->disabledOn('edit')->native(false)
                    ->helperText('Ο τύπος δεν αλλάζει μετά τη δημιουργία· φτιάξτε νέα ενότητα αν χρειάζεται άλλος.'),
                TextInput::make('admin_label')->label('Όνομα στη διαχείριση')->maxLength(80)
                    ->placeholder('π.χ. Προσφορές Χριστουγέννων')
                    ->helperText('Μόνο για εσάς, δεν φαίνεται στο site.'),
                TextInput::make('title')->label('Τίτλος στο site')->maxLength(120)
                    ->hidden($is('slider')),
                TextInput::make('anchor')->label('Σύνδεσμος (#)')->prefix('#')->alphaDash()->maxLength(40)
                    ->helperText('π.χ. «paketa» → η ενότητα ανοίγει από το /#paketa'),
                TextInput::make('settings.menu_label')->label('Όνομα στο μενού')->maxLength(20)
                    ->helperText('Συμπληρωμένο → η ενότητα μπαίνει στο μενού του site. Χρειάζεται και σύνδεσμο (#).'),
                TextInput::make('subtitle')->label('Υπότιτλος')->maxLength(300)->columnSpanFull()
                    ->hidden($is('slider')),
            ]),

            Section::make('Πού φαίνεται')->columns(4)->columnSpanFull()
                ->description('Κινητό: έως 639px · Tablet: 640–1023px · Υπολογιστής: από 1024px')
                ->schema([
                    Toggle::make('show_mobile')->label('Κινητό')->default(true)->inline(false),
                    Toggle::make('show_tablet')->label('Tablet')->default(true)->inline(false),
                    Toggle::make('show_desktop')->label('Υπολογιστής')->default(true)->inline(false),
                    Toggle::make('is_active')->label('Ενεργή')->default(true)->inline(false),
                    DateTimePicker::make('starts_at')->label('Εμφάνιση από')->seconds(false)->native(false)->columnSpan(2),
                    DateTimePicker::make('ends_at')->label('Εμφάνιση έως')->seconds(false)->native(false)->after('starts_at')->columnSpan(2),
                ]),

            Section::make('Εμφάνιση')->columns(4)->columnSpanFull()->collapsible()->collapsed()
                ->description('Φόντο, αποστάσεις, πλάτος και τίτλος της ενότητας.')
                ->schema([
                    Select::make('settings.style.bg')->label('Φόντο')->options(HomeSection::BACKGROUNDS)->default('none')->live()->native(false),
                    ColorPicker::make('settings.style.color')->label('Χρώμα φόντου')->visible(fn (Get $get) => $get('settings.style.bg') === 'custom'),
                    FileUpload::make('settings.style.image')->label('Εικόνα φόντου')->visible(fn (Get $get) => $get('settings.style.bg') === 'image')
                        ->disk('public')->directory('sections')->visibility('public')->image()->imageEditor()->maxSize(4096),
                    Select::make('settings.style.overlay')->label('Σκίαση πάνω στην εικόνα')->visible(fn (Get $get) => $get('settings.style.bg') === 'image')
                        ->options([20 => 'Λίγη', 40 => 'Μέτρια', 55 => 'Αρκετή', 70 => 'Πολλή'])->default(55),
                    Select::make('settings.style.tone')->label('Χρώμα κειμένου')->default('auto')->native(false)
                        ->options(['auto' => 'Αυτόματο', 'light' => 'Ανοιχτό (για σκούρο φόντο)', 'dark' => 'Σκούρο (για ανοιχτό φόντο)']),
                    Select::make('settings.style.pad')->label('Απόσταση πάνω/κάτω')->default('normal')->native(false)
                        ->options(['none' => 'Καθόλου', 'compact' => 'Μικρή', 'normal' => 'Κανονική', 'large' => 'Μεγάλη']),
                    Select::make('settings.style.width')->label('Πλάτος περιεχομένου')->default('normal')->native(false)
                        ->options(['narrow' => 'Στενό', 'normal' => 'Κανονικό', 'full' => 'Όλη η οθόνη']),
                    Select::make('settings.style.align')->label('Στοίχιση')->default('auto')->native(false)
                        ->options(['auto' => 'Όπως είναι σχεδιασμένη', 'center' => 'Στο κέντρο']),
                    Select::make('settings.style.title_size')->label('Μέγεθος τίτλου')->default('md')->native(false)
                        ->options(['sm' => 'Μικρό', 'md' => 'Κανονικό', 'lg' => 'Μεγάλο', 'xl' => 'Πολύ μεγάλο']),
                    Toggle::make('settings.style.animate')->label('Ομαλή εμφάνιση με την κύλιση')->default(true)->inline(false),
                ]),

            // ---- Ρυθμίσεις ανά τύπο ----
            Section::make('Ρυθμίσεις slider')->columns(2)->columnSpanFull()->visible($is('slider'))
                ->description('Τα ίδια τα slides διαχειρίζονται από «Slider αρχικής».')
                ->schema([
                    Select::make('settings.interval')->label('Χρόνος ανά slide')->default(7)
                        ->options([5 => '5 δευτερόλεπτα', 7 => '7 δευτερόλεπτα', 10 => '10 δευτερόλεπτα', 15 => '15 δευτερόλεπτα']),
                    ToggleButtons::make('settings.height')->label('Ύψος')->inline()->default('tall')
                        ->options(['short' => 'Χαμηλό', 'tall' => 'Ψηλό', 'full' => 'Όλη η οθόνη']),
                ]),

            Section::make('Κουμπιά')->columns(2)->columnSpanFull()->visible($is('quote_cta'))
                ->description('Το πρώτο κουμπί ανοίγει το Studio Καμπάνιας. Το δεύτερο, την εγγραφή (ή «Οι προσφορές μου» για όσους είναι ήδη πελάτες).')
                ->schema([
                    TextInput::make('settings.primary_label')->label('Κύριο κουμπί')->maxLength(40)->placeholder('Βγάλτε προσφορά σε 2 λεπτά'),
                    TextInput::make('settings.secondary_label')->label('Δεύτερο κουμπί')->maxLength(40)->placeholder('Δωρεάν εγγραφή επιχείρησης'),
                    Toggle::make('settings.show_steps')->label('Δείξε τα 3 βήματα')->default(true)->inline(false),
                ]),

            Section::make('Ρυθμίσεις σταθμών')->columnSpanFull()->visible($is('stations'))->schema([
                ToggleButtons::make('settings.layout')->label('Εμφάνιση')->inline()->default('cards')
                    ->options(['cards' => 'Κάρτες με «τι παίζει»', 'compact' => 'Συμπαγής λίστα λογοτύπων']),
            ]),

            Section::make('Ρυθμίσεις πακέτων')->columns(3)->columnSpanFull()->visible($is('packages'))->schema([
                Select::make('settings.source')->label('Ποια πακέτα')->default('all')->required()->live()
                    ->options(['all' => 'Όλα τα ενεργά', 'featured' => 'Μόνο τα προβεβλημένα', 'offers' => 'Μόνο προσφορές', 'chosen' => 'Συγκεκριμένα']),
                Select::make('settings.package_ids')->label('Επιλογή πακέτων')->multiple()
                    ->options(fn () => Package::query()->orderBy('sort')->pluck('name', 'id'))
                    ->visible(fn (Get $get) => $get('settings.source') === 'chosen'),
                Select::make('settings.limit')->label('Πόσα το πολύ')->default(6)
                    ->options([3 => 3, 4 => 4, 6 => 6, 8 => 8, 12 => 12]),
            ]),

            Section::make('Ρυθμίσεις πάνελ προσφορών')->columns(3)->columnSpanFull()->visible($is('offers'))
                ->description('Τα πάνελ γίνονται slider όταν οι προσφορές είναι πάνω από 3. Η ενότητα κρύβεται μόνη της όταν δεν υπάρχει ενεργή προσφορά.')
                ->schema([
                    Select::make('settings.source')->label('Τι δείχνει')->default('offers')->required()
                        ->options(['offers' => 'Μόνο προσφορές (με λήξη)', 'featured' => 'Μόνο προβεβλημένα', 'all' => 'Όλα τα ενεργά πακέτα', 'chosen' => 'Συγκεκριμένα']),
                    Select::make('settings.package_ids')->label('Επιλογή')->multiple()
                        ->options(fn () => Package::query()->orderBy('sort')->pluck('name', 'id'))
                        ->visible(fn (Get $get) => $get('settings.source') === 'chosen'),
                    Select::make('settings.limit')->label('Πόσες το πολύ')->default(12)->options([3 => 3, 6 => 6, 9 => 9, 12 => 12, 20 => 20]),
                    Toggle::make('settings.autoplay')->label('Αυτόματη κύλιση')->default(true)->inline(false),
                ]),

            Section::make('Ρυθμίσεις ειδήσεων')->columnSpanFull()->visible($is('news'))->schema([
                Select::make('settings.count')->label('Πόσες ειδήσεις')->default(7)
                    ->options([4 => 4, 5 => 5, 7 => 7, 9 => 9]),
            ]),

            Section::make('Τα μέσα')->columnSpanFull()->visible($is('media'))->schema([
                ToggleButtons::make('settings.layout')->label('Διάταξη')->inline()->default('cards')
                    ->options(['cards' => 'Μεγάλες κάρτες', 'rows' => 'Εναλλασσόμενες σειρές με εικόνα', 'minimal' => 'Λιτό πλέγμα']),
                Repeater::make('settings.items')->label('')->addActionLabel('Προσθήκη μέσου')
                    ->helperText('Κενό = τα τρία μέσα μας με έτοιμα κείμενα.')
                    ->maxItems(6)->reorderable()->collapsible()->columns(2)
                    ->itemLabel(fn (array $state) => $state['title'] ?? null)
                    ->schema([
                        TextInput::make('eyebrow')->label('Μικρή λέξη πάνω')->maxLength(30)->placeholder('π.χ. Ακούγεσαι'),
                        TextInput::make('title')->label('Τίτλος')->required()->maxLength(60),
                        Textarea::make('text')->label('Κείμενο')->required()->maxLength(300)->rows(2)->columnSpanFull(),
                        TextInput::make('stat')->label('Αριθμός που ξεχωρίζει')->maxLength(12)->placeholder('π.χ. 12'),
                        TextInput::make('stat_label')->label('Τι μετρά')->maxLength(40)->placeholder('π.χ. σταθμοί FM'),
                        Select::make('icon')->label('Εικονίδιο')->default('radio')->options(self::ICONS),
                        ColorPicker::make('color')->label('Χρώμα')->default('#c80c92'),
                        TextInput::make('url')->label('Link (προαιρετικό)')->maxLength(255)->rule('regex:/^(\/(?!\/)|https:\/\/)/'),
                        TextInput::make('cta')->label('Κείμενο link')->maxLength(30)->placeholder('Μάθετε περισσότερα'),
                        FileUpload::make('image')->label('Εικόνα (για διάταξη με σειρές)')->columnSpanFull()
                            ->disk('public')->directory('sections')->visibility('public')->image()->imageEditor()->maxSize(4096),
                    ]),
                Toggle::make('settings.show_logos')->label('Λογότυπα σταθμών στην κάρτα του ραδιοφώνου')->default(true),
                Toggle::make('settings.show_cta')->label('Κουμπί «Βγάλτε προσφορά» στο τέλος')->default(true),
                TextInput::make('settings.note')->label('Σημείωση κάτω από τα μέσα')->maxLength(200),
            ]),

            Section::make('Η εφαρμογή')->columns(2)->columnSpanFull()->visible($is('app'))
                ->description('Τα επίσημα σήματα «App Store» και «Google Play» τα κατεβάζετε από Apple και Google (υπάρχουν και στα ελληνικά) και τα ανεβάζετε εδώ. Χωρίς αυτά εμφανίζονται απλά κουμπιά λήψης.')
                ->schema([
                    TextInput::make('settings.ios_url')->label('Link App Store')->url()->rule('starts_with:https://')
                        ->default('https://apps.apple.com/us/app/on-air-media-group/id6762763280'),
                    TextInput::make('settings.android_url')->label('Link Google Play')->url()->rule('starts_with:https://')
                        ->default('https://play.google.com/store/apps/details?id=gr.radio.onairmediagroup'),
                    FileUpload::make('settings.ios_badge')->label('Σήμα App Store (SVG/PNG)')
                        ->disk('public')->directory('badges')->visibility('public')->image()->acceptedFileTypes(['image/svg+xml', 'image/png', 'image/webp'])->maxSize(512),
                    FileUpload::make('settings.android_badge')->label('Σήμα Google Play (SVG/PNG)')
                        ->disk('public')->directory('badges')->visibility('public')->image()->acceptedFileTypes(['image/svg+xml', 'image/png', 'image/webp'])->maxSize(512),
                    FileUpload::make('settings.phone_image')->label('Στιγμιότυπο της εφαρμογής')->helperText('Κάθετη εικόνα από την οθόνη του κινητού.')
                        ->disk('public')->directory('sections')->visibility('public')->image()->imageEditor()->maxSize(4096),
                    ToggleButtons::make('settings.layout')->label('Εικόνα')->inline()->default('image-right')
                        ->options(['image-right' => 'Δεξιά', 'image-left' => 'Αριστερά']),
                    Repeater::make('settings.features')->label('Τι κάνει η εφαρμογή')->simple(TextInput::make('line')->maxLength(90))
                        ->addActionLabel('Προσθήκη')->defaultItems(0)->columnSpanFull()
                        ->helperText('Κενό = ζωντανά οι 12 σταθμοί, CarPlay & Android Auto, ειδήσεις.'),
                    Toggle::make('settings.qr')->label('QR code για λήψη από υπολογιστή')->default(true)->inline(false),
                ]),

            Section::make('Αριθμοί')->columnSpanFull()->visible($is('stats'))->schema([
                Repeater::make('settings.items')->label('')->addActionLabel('Προσθήκη αριθμού')->maxItems(6)->columns(3)->reorderable()
                    ->schema([
                        TextInput::make('value')->label('Αριθμός')->required()->maxLength(12)->placeholder('12'),
                        TextInput::make('label')->label('Περιγραφή')->required()->maxLength(60)->placeholder('σταθμοί FM'),
                        TextInput::make('note')->label('Μικρή σημείωση')->maxLength(80),
                    ]),
                Toggle::make('settings.count_up')->label('Ο αριθμός «μετράει» όταν εμφανίζεται')->default(true),
            ]),

            Section::make('Βήματα')->columnSpanFull()->visible($is('steps'))->schema([
                Repeater::make('settings.items')->label('')->addActionLabel('Προσθήκη βήματος')->maxItems(6)->columns(2)->reorderable()
                    ->helperText('Κενό = εγγραφή, προσφορά, ταμείο, στον αέρα.')
                    ->schema([
                        TextInput::make('title')->label('Τίτλος')->required()->maxLength(60),
                        TextInput::make('text')->label('Κείμενο')->maxLength(200),
                    ]),
                TextInput::make('settings.cta_label')->label('Κουμπί στο τέλος')->maxLength(30)->placeholder('Ξεκινήστε τώρα'),
            ]),

            Section::make('Ερωτήσεις')->columnSpanFull()->visible($is('faq'))->schema([
                Repeater::make('settings.items')->label('')->addActionLabel('Προσθήκη ερώτησης')->reorderable()->collapsible()
                    ->itemLabel(fn (array $state) => $state['q'] ?? null)
                    ->schema([
                        TextInput::make('q')->label('Ερώτηση')->required()->maxLength(160),
                        Textarea::make('a')->label('Απάντηση')->required()->rows(3)->maxLength(1500),
                    ]),
                Toggle::make('settings.schema')->label('Εμφάνιση στο Google ως «Συχνές ερωτήσεις»')->default(true)
                    ->helperText('Προσθέτει τα δομημένα δεδομένα FAQ για SEO.'),
            ]),

            Section::make('Λογότυπα')->columnSpanFull()->visible($is('logos'))->schema([
                Repeater::make('settings.items')->label('')->addActionLabel('Προσθήκη λογοτύπου')->columns(3)->reorderable()->grid(2)
                    ->schema([
                        FileUpload::make('image')->label('Λογότυπο')->required()->disk('public')->directory('logos')->visibility('public')->image()->maxSize(1024),
                        TextInput::make('name')->label('Όνομα')->required()->maxLength(60),
                        TextInput::make('url')->label('Link')->maxLength(255)->rule('regex:/^https:\/\//'),
                    ]),
                Toggle::make('settings.grayscale')->label('Ασπρόμαυρα (χρώμα στο πέρασμα του ποντικιού)')->default(true),
                Toggle::make('settings.marquee')->label('Συνεχής κύλιση')->default(false),
            ]),

            Section::make('Βίντεο')->columns(2)->columnSpanFull()->visible($is('video'))
                ->description('Για προστασία των επισκεπτών, το βίντεο φορτώνει μόνο όταν πατήσουν «Αναπαραγωγή» (χωρίς cookies μέχρι τότε).')
                ->schema([
                    TextInput::make('settings.url')->label('Link YouTube ή Vimeo')->required()->url()->columnSpanFull()
                        ->rule('regex:/^https:\/\/(www\.)?(youtube\.com|youtu\.be|vimeo\.com)\//'),
                    FileUpload::make('settings.poster')->label('Εικόνα πριν την αναπαραγωγή')->disk('public')->directory('sections')->visibility('public')->image()->maxSize(4096),
                    Textarea::make('settings.caption')->label('Λεζάντα')->rows(2)->maxLength(300),
                ]),

            Section::make('Επικοινωνία')->columns(2)->columnSpanFull()->visible($is('contact'))
                ->description('Τηλέφωνο, email και διεύθυνση έρχονται από τις «Ρυθμίσεις site».')
                ->schema([
                    Textarea::make('settings.hours')->label('Ωράριο')->rows(3)->maxLength(300)->placeholder("Δευτέρα–Παρασκευή 09:00–17:00"),
                    TextInput::make('settings.map_url')->label('Link χάρτη')->url()->rule('starts_with:https://'),
                ]),

            Section::make('Banner')->columns(2)->columnSpanFull()->visible($is('banner'))->schema([
                FileUpload::make('settings.image')->label('Εικόνα')
                    ->disk('public')->directory('sections')->visibility('public')
                    ->image()->imageEditor()->maxSize(4096),
                ToggleButtons::make('settings.layout')->label('Διάταξη')->inline()->default('image-right')
                    ->options(['image-right' => 'Εικόνα δεξιά', 'image-left' => 'Εικόνα αριστερά', 'background' => 'Εικόνα φόντο']),
                TextInput::make('settings.cta_label')->label('Κείμενο κουμπιού')->maxLength(30),
                TextInput::make('settings.cta_url')->label('Link κουμπιού')->maxLength(255)
                    ->rule('regex:/^(\/(?!\/)|https:\/\/)/')
                    ->validationMessages(['regex' => 'Το link ξεκινά με / ή με https://']),
                ColorPicker::make('settings.color')->label('Χρώμα φόντου')->default('#121a33'),
            ]),

            Section::make('Κείμενο')->columnSpanFull()->visible($is('text'))->schema([
                RichEditor::make('settings.body')->label('')
                    ->toolbarButtons(['bold', 'italic', 'link', 'h2', 'h3', 'bulletList', 'orderedList', 'blockquote', 'undo', 'redo']),
                ToggleButtons::make('settings.align')->label('Στοίχιση')->inline()->default('left')
                    ->options(['left' => 'Αριστερά', 'center' => 'Κέντρο']),
            ]),
        ]);
    }
}
