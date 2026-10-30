<?php

namespace App\Filament\Resources\Campaigns\Pages;

use App\Filament\Resources\Campaigns\CampaignResource;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;

class CreateCampaign extends CreateRecord
{
    protected static string $resource = CampaignResource::class;

    protected static ?string $title = 'Νέα μαζική αποστολή';

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        unset($data['audience_count']);

        return $data;
    }

    protected function afterCreate(): void
    {
        $this->getRecord()->forceFill(['created_by_id' => Filament::auth()->id()])->save();
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('edit', ['record' => $this->getRecord()]);
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Αποθηκεύτηκε ως πρόχειρο. Στείλτε μια δοκιμή στο email σας πριν την αποστολή.';
    }
}
