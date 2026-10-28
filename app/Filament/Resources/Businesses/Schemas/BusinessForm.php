<?php

namespace App\Filament\Resources\Businesses\Schemas;

use App\Models\Business;
use App\Rules\GreekVat;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class BusinessForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Επιχείρηση')->columns(2)->columnSpanFull()->schema([
                TextInput::make('legal_name')->label('Επωνυμία')->required()->maxLength(150),
                TextInput::make('trade_name')->label('Διακριτικός τίτλος')->maxLength(150),
                Select::make('business_type')->label('Είδος')->options(Business::TYPES)->required(),
                TextInput::make('business_type_other')->label('Άλλο είδος')->maxLength(100),
                TextInput::make('vat')->label('ΑΦΜ')->required()->rule(new GreekVat)->unique(ignoreRecord: true),
                TextInput::make('tax_office')->label('ΔΟΥ'),
                TextInput::make('gemi')->label('ΓΕΜΗ'),
                TextInput::make('website')->label('Ιστοσελίδα')->url(),
            ]),
            Section::make('Επικοινωνία')->columns(3)->columnSpanFull()->schema([
                TextInput::make('contact_person')->label('Υπεύθυνος')->required(),
                TextInput::make('mobile')->label('Κινητό')->tel()->required(),
                TextInput::make('phone')->label('Σταθερό')->tel(),
                TextInput::make('address')->label('Διεύθυνση')->required()->columnSpan(2),
                TextInput::make('city')->label('Πόλη')->required(),
                TextInput::make('postal_code')->label('Τ.Κ.')->required(),
            ]),
            Section::make('Εσωτερικές σημειώσεις')->columnSpanFull()
                ->description('Τις βλέπει μόνο το προσωπικό.')
                ->schema([
                    Textarea::make('admin_notes')->label('')->rows(4)->maxLength(5000),
                ]),
        ]);
    }
}
