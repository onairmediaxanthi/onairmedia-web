<?php

namespace App\Filament\Resources\Packages\Schemas;

use App\Models\Package;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class PackageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Το πακέτο')->columns(2)->columnSpanFull()->schema([
                TextInput::make('name')->label('Όνομα')->required()->maxLength(80)
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn (Set $set, ?string $state, string $operation) => $operation === 'create'
                        ? $set('slug', Str::slug(Str::ascii((string) $state))) : null),
                TextInput::make('slug')->label('Κωδικός URL')->required()->alphaDash()->unique(ignoreRecord: true)->maxLength(80),
                TextInput::make('tagline')->label('Σύντομη περιγραφή')->maxLength(140)->columnSpanFull()
                    ->placeholder('π.χ. Ραδιόφωνο και Οθόνη Πόλης στην ίδια πόλη'),
                Textarea::make('description')->label('Αναλυτική περιγραφή')->rows(3)->maxLength(1000)->columnSpanFull(),
                CheckboxList::make('media_mix')->label('Μέσα που περιλαμβάνει')->options(Package::MEDIA)->columns(4)->columnSpanFull()
                    ->formatStateUsing(fn ($state) => is_string($state) ? array_filter(explode(',', $state)) : ($state ?? []))
                    ->dehydrateStateUsing(fn ($state) => implode(',', (array) $state)),
                Repeater::make('includes')->label('Τι περιλαμβάνει (λίστα)')->simple(
                    TextInput::make('line')->required()->maxLength(120)->placeholder('π.χ. 5 μεταδόσεις την ημέρα σε 3 σταθμούς'),
                )->addActionLabel('Προσθήκη γραμμής')->reorderable()->columnSpanFull()->defaultItems(3),
                TextInput::make('duration_days')->label('Διάρκεια (ημέρες)')->numeric()->minValue(1)->maxValue(366),
            ]),

            Section::make('Τιμή')->columns(3)->columnSpanFull()
                ->description('Οι τιμές φαίνονται μόνο σε συνδεδεμένους επαγγελματίες. Οι επισκέπτες βλέπουν «Δείτε την τιμή».')
                ->schema([
                    TextInput::make('price')->label('Τιμή (χωρίς ΦΠΑ)')->numeric()->minValue(0)->suffix('€')->required(),
                    TextInput::make('compare_at_price')->label('Αρχική τιμή (για έκπτωση)')->numeric()->minValue(0)->suffix('€')
                        ->gt('price')->helperText('Συμπληρώστε μόνο σε προσφορά: εμφανίζεται διαγραμμένη.'),
                    TextInput::make('badge')->label('Ετικέτα')->maxLength(30)->placeholder('π.χ. Δημοφιλές'),
                ]),

            Section::make('Εμφάνιση & διάρκεια προσφοράς')->columns(3)->columnSpanFull()->schema([
                ColorPicker::make('color')->label('Χρώμα')->default('#c80c92'),
                FileUpload::make('image_path')->label('Εικόνα (προαιρετική)')
                    ->disk('public')->directory('packages')->visibility('public')->image()->imageEditor()->maxSize(3072),
                Toggle::make('is_featured')->label('Προβεβλημένο')->inline(false)
                    ->helperText('Ξεχωρίζει στην αρχική.'),
                Toggle::make('is_offer')->label('Είναι προσφορά')->inline(false)->live(),
                DateTimePicker::make('available_from')->label('Διαθέσιμο από')->seconds(false)->native(false),
                DateTimePicker::make('available_until')->label('Λήξη προσφοράς')->seconds(false)->native(false)->after('available_from')
                    ->required(fn (Get $get) => (bool) $get('is_offer')),
                Toggle::make('is_active')->label('Ενεργό')->default(true)->inline(false),
            ]),
        ]);
    }
}
