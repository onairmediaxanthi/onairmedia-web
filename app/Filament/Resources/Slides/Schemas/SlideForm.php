<?php

namespace App\Filament\Resources\Slides\Schemas;

use App\Models\Slide;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SlideForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Κείμενο')->columns(2)->columnSpanFull()->schema([
                TextInput::make('title')->label('Τίτλος')->required()->maxLength(90)->columnSpanFull()
                    ->helperText('Σύντομος και καθαρός, έως 90 χαρακτήρες.'),
                TextInput::make('subtitle')->label('Υπότιτλος')->maxLength(160)->columnSpanFull(),
                TextInput::make('cta_label')->label('Κείμενο κουμπιού')->maxLength(30)
                    ->placeholder('π.χ. Δείτε την προσφορά'),
                TextInput::make('cta_url')->label('Link κουμπιού')->maxLength(255)
                    ->placeholder('/paketa ή https://...')
                    ->rule('regex:/^(\/(?!\/)|https:\/\/)/')
                    ->validationMessages(['regex' => 'Το link ξεκινά με / (σελίδα του site) ή με https://']),
            ]),
            Section::make('Εικόνα & εφέ')->columns(2)->columnSpanFull()->schema([
                FileUpload::make('image_path')->label('Εικόνα (υπολογιστής)')->required()
                    ->disk('public')->directory('slides')->visibility('public')
                    ->image()->imageEditor()->imageEditorAspectRatios(['16:7', '16:9'])->maxSize(4096)
                    ->helperText('Οριζόντια, τουλάχιστον 1920×840, έως 4 MB.'),
                FileUpload::make('image_mobile_path')->label('Εικόνα (κινητό, προαιρετική)')
                    ->disk('public')->directory('slides')->visibility('public')
                    ->image()->imageEditor()->imageEditorAspectRatios(['4:5', '9:16'])->maxSize(3072)
                    ->helperText('Κάθετη, για καλύτερη εμφάνιση στα κινητά.'),
                Select::make('effect')->label('Εφέ κίνησης')->options(Slide::EFFECTS)->default('zoom')->required(),
                ToggleButtons::make('text_position')->label('Θέση κειμένου')->inline()->default('left')
                    ->options(['left' => 'Αριστερά', 'center' => 'Κέντρο']),
            ]),
            Section::make('Πότε εμφανίζεται')->columns(3)->columnSpanFull()->schema([
                DateTimePicker::make('starts_at')->label('Από')->seconds(false)->native(false),
                DateTimePicker::make('ends_at')->label('Έως (λήξη προσφοράς)')->seconds(false)->native(false)->after('starts_at'),
                Toggle::make('is_active')->label('Ενεργό')->default(true)->inline(false),
            ]),
        ]);
    }
}
