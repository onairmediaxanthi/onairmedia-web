<?php

namespace App\Filament\Resources\HomeSections\Pages;

use App\Filament\Resources\HomeSections\HomeSectionResource;
use Filament\Resources\Pages\CreateRecord;

class CreateHomeSection extends CreateRecord
{
    protected static string $resource = HomeSectionResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['sort'] = (\App\Models\HomeSection::max('sort') ?? 0) + 10;

        return $data;
    }
}
