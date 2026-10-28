<?php

namespace App\Filament\Resources\HomeSections\Pages;

use App\Filament\Resources\HomeSections\HomeSectionResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListHomeSections extends ListRecords
{
    protected static string $resource = HomeSectionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('preview')->label('Προβολή αρχικής')->icon('heroicon-o-eye')
                ->color('gray')->url(route('home'), shouldOpenInNewTab: true),
            CreateAction::make()->label('Νέα ενότητα'),
        ];
    }
}
