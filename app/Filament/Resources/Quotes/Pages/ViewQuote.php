<?php

namespace App\Filament\Resources\Quotes\Pages;

use App\Filament\Resources\Quotes\QuoteResource;
use App\Models\Quote;
use App\Services\Quotes\QuotePdf;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewQuote extends ViewRecord
{
    protected static string $resource = QuoteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('pdf')->label('PDF')->icon('heroicon-o-arrow-down-tray')->color('gray')
                ->action(fn (Quote $record) => response()->streamDownload(fn () => print (QuotePdf::render($record)), QuotePdf::filename($record))),
            Action::make('update')->label('Ενημέρωση')->icon('heroicon-o-pencil-square')
                ->hidden(fn (Quote $record) => $record->trashed())
                ->fillForm(fn (Quote $record) => ['status' => $record->status, 'admin_notes' => $record->admin_notes])
                ->schema([
                    Select::make('status')->label('Κατάσταση')->options(Quote::STATUSES)->required()->native(false),
                    Textarea::make('admin_notes')->label('Εσωτερικές σημειώσεις')->rows(4)->maxLength(5000)
                        ->helperText('Δεν τις βλέπει ο πελάτης.'),
                ])
                ->action(function (Quote $record, array $data) {
                    $record->status = $data['status'];
                    $record->admin_notes = $data['admin_notes'];
                    $record->save();
                    Notification::make()->success()->title('Αποθηκεύτηκε')->send();
                }),
        ];
    }
}
