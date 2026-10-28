<?php

namespace App\Filament\Resources\Packages\Pages;

use App\Filament\Resources\Packages\PackageResource;
use App\Filament\Resources\Packages\Schemas\PackageForm;
use Filament\Actions\DeleteAction;
use Filament\Actions\ReplicateAction;
use Filament\Resources\Pages\EditRecord;

class EditPackage extends EditRecord
{
    protected static string $resource = PackageResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        return PackageForm::fillBuilder($data);
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return PackageForm::applyBuilder($data);
    }

    protected function getHeaderActions(): array
    {
        return [
            ReplicateAction::make()->label('Αντίγραφο')
                ->mutateRecordDataUsing(fn (array $data) => ['name' => $data['name'].' (αντίγραφο)', 'slug' => $data['slug'].'-'.substr(md5(uniqid()), 0, 4), 'is_active' => false] + $data),
            DeleteAction::make(),
        ];
    }
}
