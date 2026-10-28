<?php

namespace App\Filament\Resources\Businesses\Schemas;

use App\Models\Business;
use App\Rules\GreekVat;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class BusinessForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Λογαριασμός')->columns(2)->columnSpanFull()->visibleOn('create')
                ->description('Ο πελάτης θα μπαίνει στο Studio του με αυτό το email. Δεν βλέπει τίποτα από τη διαχείριση.')
                ->schema([
                    TextInput::make('account_email')->label('Email πελάτη')->email()->required()->maxLength(255)
                        ->unique(table: 'users', column: 'email')
                        ->validationMessages(['unique' => 'Υπάρχει ήδη λογαριασμός με αυτό το email.']),
                    Toggle::make('send_invite')->label('Αποστολή πρόσκλησης τώρα')->default(true)->inline(false)
                        ->helperText('Ο πελάτης λαμβάνει email για να ορίσει κωδικό. Μπορείτε να τη στείλετε και αργότερα.'),
                ]),
            Section::make('Λογαριασμός')->columns(4)->columnSpanFull()->visibleOn('edit')->schema([
                TextEntry::make('user.email')->label('Email')->copyable(),
                TextEntry::make('source')->label('Προέλευση')->badge()
                    ->state(fn (Business $record) => $record->sourceLabel())
                    ->color(fn (Business $record) => $record->isStaffCreated() ? 'warning' : 'info'),
                TextEntry::make('activated')->label('Λογαριασμός')->badge()
                    ->state(fn (Business $record) => $record->isActivated() ? 'Ενεργός' : ($record->invited_at ? 'Πρόσκληση '.$record->invited_at->format('d/m/Y') : 'Δεν έχει ενεργοποιηθεί'))
                    ->color(fn (Business $record) => $record->isActivated() ? 'success' : 'gray'),
                TextEntry::make('terms_accepted_at')->label('Αποδοχή όρων')->dateTime('d/m/Y H:i')->placeholder('Όχι ακόμα'),
            ]),
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
