<?php

namespace App\Filament\Resources\Quotes\Pages;

use App\Filament\Resources\Quotes\Concerns\BuildsQuotes;
use App\Filament\Resources\Quotes\QuoteResource;
use App\Models\Quote;
use App\Services\Quotes\QuoteService;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Exceptions\Halt;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

/** Αλλαγές σε αποθηκευμένη προσφορά: ο πελάτης τις βλέπει αμέσως στο Studio του */
class EditQuote extends EditRecord
{
    use BuildsQuotes;

    protected static string $resource = QuoteResource::class;

    public function getTitle(): string
    {
        return 'Αλλαγές στην προσφορά '.$this->getRecord()->number;
    }

    protected function authorizeAccess(): void
    {
        parent::authorizeAccess();
        abort_if($this->getRecord()->trashed(), 404);
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        return self::stateFromQuote($this->getRecord());
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var Quote $record */
        try {
            return app(QuoteService::class)->updateByStaff(
                $record, Filament::auth()->user(), $data['title'], self::specsFrom($data), (int) $data['days'],
                $data['starts_on'] ?? null, $data['intro'] ?? null,
            );
        } catch (InvalidArgumentException $e) {
            Notification::make()->danger()->title($e->getMessage())->send();

            throw new Halt;
        }
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Οι αλλαγές αποθηκεύτηκαν. Ο πελάτης τις βλέπει ήδη· πατήστε «Νέα αποστολή» για να του τη στείλετε ξανά.';
    }

    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }
}
