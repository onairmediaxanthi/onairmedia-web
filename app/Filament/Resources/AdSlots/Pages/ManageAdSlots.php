<?php

namespace App\Filament\Resources\AdSlots\Pages;

use App\Filament\Resources\AdSlots\AdSlotResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageAdSlots extends ManageRecords
{
    protected static string $resource = AdSlotResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('Νέα θέση')];
    }
}
