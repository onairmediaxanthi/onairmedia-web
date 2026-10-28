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
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class HomeSectionForm
{
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
                Select::make('settings.source')->label('Ποια πακέτα')->default('all')->required()
                    ->options(['all' => 'Όλα τα ενεργά', 'featured' => 'Μόνο τα προβεβλημένα', 'offers' => 'Μόνο προσφορές', 'chosen' => 'Συγκεκριμένα']),
                Select::make('settings.package_ids')->label('Επιλογή πακέτων')->multiple()
                    ->options(fn () => Package::query()->orderBy('sort')->pluck('name', 'id'))
                    ->visible(fn (Get $get) => $get('settings.source') === 'chosen'),
                Select::make('settings.limit')->label('Πόσα το πολύ')->default(6)
                    ->options([3 => 3, 4 => 4, 6 => 6, 8 => 8, 12 => 12]),
            ]),

            Section::make('Ρυθμίσεις ειδήσεων')->columnSpanFull()->visible($is('news'))->schema([
                Select::make('settings.count')->label('Πόσες ειδήσεις')->default(7)
                    ->options([4 => 4, 5 => 5, 7 => 7, 9 => 9]),
            ]),

            Section::make('Τα μέσα')->columnSpanFull()->visible($is('media'))->schema([
                Repeater::make('settings.items')->label('')->addActionLabel('Προσθήκη μέσου')
                    ->maxItems(6)->reorderable()->collapsible()
                    ->itemLabel(fn (array $state) => $state['title'] ?? null)
                    ->schema([
                        TextInput::make('title')->label('Τίτλος')->required()->maxLength(60),
                        TextInput::make('text')->label('Κείμενο')->required()->maxLength(240),
                        TextInput::make('url')->label('Link (προαιρετικό)')->maxLength(255)
                            ->rule('regex:/^(\/(?!\/)|https:\/\/)/'),
                    ]),
                TextInput::make('settings.note')->label('Σημείωση κάτω από τα μέσα')->maxLength(200),
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
