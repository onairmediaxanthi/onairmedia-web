<?php

namespace App\Filament\Resources\CallbackRequests\Pages;

use App\Filament\Resources\Businesses\BusinessResource;
use App\Filament\Resources\CallbackRequests\CallbackRequestResource;
use App\Models\CallbackRequest;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Facades\Filament;
use Filament\Resources\Pages\EditRecord;

class EditCallbackRequest extends EditRecord
{
    protected static string $resource = CallbackRequestResource::class;

    public function getTitle(): string
    {
        return 'Αίτημα · '.$this->getRecord()->fullName();
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->getRecord()->forceFill(['handled_by_id' => Filament::auth()->id(), 'handled_at' => now()]);

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('call')->label('Κλήση')->icon('heroicon-o-phone')->url(fn (CallbackRequest $record) => 'tel:+30'.$record->mobile),
            Action::make('customer')->label('Νέος πελάτης')->icon('heroicon-o-user-plus')->color('gray')
                ->visible(fn (CallbackRequest $record) => ! $record->business_id)
                ->url(fn (CallbackRequest $record) => BusinessResource::getUrl('create', ['afm' => $record->vat, 'onoma' => $record->fullName(), 'kinito' => $record->mobile])),
            DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
