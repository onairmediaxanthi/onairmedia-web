<?php

namespace App\Filament\Resources\Stations\Schemas;

use App\Support\Audience;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class StationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make()->columnSpanFull()->tabs([
                Tab::make('Βασικά')->icon('heroicon-o-radio')->columns(2)->schema([
                    TextInput::make('name')->label('Όνομα σταθμού')->required()->maxLength(100)
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn (Set $set, ?string $state, string $operation) => $operation === 'create'
                            ? $set('slug', Str::slug(Str::ascii((string) $state))) : null),
                    TextInput::make('slug')->label('Διεύθυνση σελίδας')->prefix('/radio/')
                        ->required()->alphaDash()->unique(ignoreRecord: true)->maxLength(100),
                    TextInput::make('frequency')->label('Συχνότητα')->numeric()->step(0.1)
                        ->minValue(87.5)->maxValue(108)->suffix('FM'),
                    TextInput::make('slogan')->label('Σλόγκαν')->maxLength(150),
                    TextInput::make('genre')->label('Μουσικό είδος')->maxLength(100),
                    TextInput::make('coverage')->label('Περιοχή κάλυψης')->maxLength(150)
                        ->placeholder('π.χ. Ξάνθη, Κομοτηνή'),
                    ColorPicker::make('color')->label('Χρώμα σταθμού')->required(),
                    Toggle::make('is_active')->label('Εμφανίζεται στο site')->default(true)->inline(false),
                    RichEditor::make('description')->label('Προφίλ σταθμού')->columnSpanFull()
                        ->toolbarButtons(['bold', 'italic', 'link', 'h2', 'h3', 'bulletList', 'orderedList', 'undo', 'redo']),
                ]),

                Tab::make('Εικόνες')->icon('heroicon-o-photo')->columns(2)->schema([
                    FileUpload::make('logo_path')->label('Λογότυπο')
                        ->disk('public')->directory('stations')->visibility('public')
                        ->image()->imageEditor()->maxSize(2048)
                        ->acceptedFileTypes(['image/webp', 'image/png', 'image/jpeg', 'image/svg+xml'])
                        ->helperText('WebP ή PNG με διάφανο φόντο, έως 2 MB.'),
                    FileUpload::make('cover_path')->label('Εικόνα εξωφύλλου')
                        ->disk('public')->directory('stations/covers')->visibility('public')
                        ->image()->imageEditor()->maxSize(4096)
                        ->helperText('Οριζόντια φωτογραφία για τη σελίδα του σταθμού, έως 4 MB.'),
                ]),

                Tab::make('Stream & «τι παίζει»')->icon('heroicon-o-signal')->schema([
                    TextInput::make('stream_url')->label('Διεύθυνση stream')->url()->required()
                        ->helperText('Το link του live24 (ή άλλου παρόχου) που παίζει ο player.'),
                    TextInput::make('now_playing_mount')->label('Mount για «τι παίζει τώρα»')
                        ->placeholder('/star888xanthi')
                        ->helperText('Το τελευταίο κομμάτι του stream link με «/» μπροστά. Κενό = δεν εμφανίζεται τραγούδι.'),
                ]),

                Tab::make('Κοινό')->icon('heroicon-o-user-group')->schema(array_merge(
                    [Radio::make('audience.gender')->label('Φύλο ακροατών')->options(Audience::GENDER)->inline()],
                    array_map(
                        fn (string $key, array $g) => CheckboxList::make("audience.$key")->label($g['label'])
                            ->helperText($g['help'])->options($g['options'])->bulkToggleable()
                            ->columns($key === 'class' ? 1 : ($key === 'profiles' ? 3 : 6))->gridDirection('row'),
                        array_keys(Audience::GROUPS), Audience::GROUPS,
                    ),
                )),

                Tab::make('Social & SEO')->icon('heroicon-o-magnifying-glass')->schema([
                    TextInput::make('website')->label('Ιστοσελίδα σταθμού')->url(),
                    KeyValue::make('socials')->label('Social media')
                        ->keyLabel('Δίκτυο')->valueLabel('Link')
                        ->keyPlaceholder('facebook')->valuePlaceholder('https://...'),
                    Section::make('Google')->compact()->schema([
                        TextInput::make('seo_title')->label('Τίτλος για Google')->maxLength(70)
                            ->helperText('Έως 60–70 χαρακτήρες. Κενό = αυτόματα από το όνομα.'),
                        Textarea::make('seo_description')->label('Περιγραφή για Google')->maxLength(300)->rows(3)
                            ->helperText('Έως 155 χαρακτήρες για να φαίνεται ολόκληρη.'),
                    ]),
                ]),
            ]),
        ]);
    }
}
