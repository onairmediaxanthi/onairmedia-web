<?php

namespace App\Filament\Resources\Businesses\Pages;

use App\Filament\Resources\Businesses\BusinessResource;
use App\Filament\Resources\Quotes\QuoteResource;
use App\Models\Business;
use App\Services\CustomerInvites;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditBusiness extends EditRecord
{
    protected static string $resource = BusinessResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('quote')->label('Νέα προσφορά')->icon('heroicon-o-document-plus')
                ->url(fn (Business $record) => QuoteResource::getUrl('create', ['pelatis' => $record->id])),
            Action::make('invite')->label(fn (Business $record) => $record->invited_at ? 'Νέα πρόσκληση' : 'Αποστολή πρόσκλησης')
                ->icon('heroicon-o-envelope')->color('gray')
                ->visible(fn (Business $record) => ! $record->isActivated())
                ->requiresConfirmation()
                ->modalDescription(fn (Business $record) => 'Θα σταλεί email στο '.$record->user?->email.' για να ορίσει κωδικό και να μπει στο Studio του.')
                ->action(function (Business $record) {
                    app(CustomerInvites::class)->send($record);
                    Notification::make()->success()->title('Η πρόσκληση στάλθηκε')->send();
                }),
            DeleteAction::make(),
        ];
    }
}
