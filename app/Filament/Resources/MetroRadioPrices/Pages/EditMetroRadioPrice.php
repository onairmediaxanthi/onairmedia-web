<?php

namespace App\Filament\Resources\MetroRadioPrices\Pages;

use App\Filament\Resources\MetroRadioPrices\MetroRadioPriceResource;
use Filament\Resources\Pages\EditRecord;

class EditMetroRadioPrice extends EditRecord
{
    protected static string $resource = MetroRadioPriceResource::class;

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
