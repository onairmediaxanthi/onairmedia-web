<?php

namespace App\Filament\Resources\Songs\Pages;

use App\Filament\Resources\Songs\SongResource;
use Filament\Resources\Pages\EditRecord;

class EditSong extends EditRecord
{
    protected static string $resource = SongResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Αν σημειωθεί ως «όχι τραγούδι», βγαίνει και από το ιστορικό
        if (! empty($data['ignored']) && ! $this->getRecord()->ignored) {
            $this->getRecord()->airplays()->delete();
            $this->getRecord()->daily()->delete();
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
