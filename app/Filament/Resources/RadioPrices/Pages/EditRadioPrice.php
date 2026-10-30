<?php

namespace App\Filament\Resources\RadioPrices\Pages;

use App\Filament\Resources\RadioPrices\RadioPriceResource;
use Filament\Resources\Pages\EditRecord;

class EditRadioPrice extends EditRecord
{
    protected static string $resource = RadioPriceResource::class;

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
