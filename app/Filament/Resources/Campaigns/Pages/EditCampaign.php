<?php

namespace App\Filament\Resources\Campaigns\Pages;

use App\Filament\Resources\Campaigns\CampaignResource;
use App\Models\Campaign;
use App\Services\CampaignSender;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Throwable;

class EditCampaign extends EditRecord
{
    protected static string $resource = CampaignResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        unset($data['audience_count']);

        return $data;
    }

    protected function getFormActions(): array
    {
        return $this->getRecord()->isEditable() ? parent::getFormActions() : [];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('test')->label('Δοκιμή στο email μου')->icon('heroicon-o-beaker')->color('gray')
                ->action(function (Campaign $record) {
                    $this->save(shouldRedirect: false, shouldSendSavedNotification: false);
                    try {
                        app(CampaignSender::class)->test($record->refresh(), Filament::auth()->user());
                        Notification::make()->success()->title('Η δοκιμή στάλθηκε στο '.Filament::auth()->user()->email)->send();
                    } catch (Throwable $e) {
                        report($e);
                        Notification::make()->danger()->title('Η δοκιμή απέτυχε')->body('Ελέγξτε τις ρυθμίσεις email.')->send();
                    }
                })->visible(fn (Campaign $record) => $record->isEditable()),

            Action::make('send')->label('Αποστολή')->icon('heroicon-o-paper-airplane')->color('primary')
                ->visible(fn (Campaign $record) => $record->isEditable())
                ->requiresConfirmation()
                ->modalHeading('Αποστολή σε πελάτες')
                ->modalDescription(fn (Campaign $record) => 'Θα σταλεί σε '.$record->audienceQuery()->count().' πελάτες που έχουν δώσει συγκατάθεση'
                    .($record->scheduled_at?->isFuture() ? ', στις '.$record->scheduled_at->format('d/m/Y H:i') : ', ξεκινώντας σε λίγα λεπτά')
                    .', '.CampaignSender::perMinute().' email το λεπτό. Δεν αναιρείται για όσα έχουν ήδη φύγει.')
                ->modalSubmitActionLabel('Ναι, αποστολή')
                ->action(function (Campaign $record) {
                    $this->save(shouldRedirect: false, shouldSendSavedNotification: false);
                    $record->refresh();
                    if ($record->audienceQuery()->count() === 0) {
                        Notification::make()->warning()->title('Δεν υπάρχει κανένας παραλήπτης με συγκατάθεση')->send();

                        return;
                    }
                    if (! $record->subject) {
                        return;
                    }
                    $record->forceFill(['status' => 'scheduled'])->save();
                    Notification::make()->success()->title('Η αποστολή ξεκινά')->body('Η πρόοδος φαίνεται στη λίστα παραληπτών πιο κάτω.')->send();
                    $this->redirect(static::getResource()::getUrl('edit', ['record' => $record]));
                }),

            Action::make('cancel')->label('Διακοπή')->icon('heroicon-o-stop')->color('danger')
                ->visible(fn (Campaign $record) => in_array($record->status, ['scheduled', 'sending'], true))
                ->requiresConfirmation()->modalDescription('Όσα email δεν έχουν φύγει ακόμα, δεν θα σταλούν.')
                ->action(function (Campaign $record) {
                    $record->recipients()->where('status', 'pending')->update(['status' => 'skipped', 'error' => 'Διακοπή']);
                    $record->forceFill(['status' => 'cancelled', 'finished_at' => now()])->save();
                    Notification::make()->success()->title('Η αποστολή διακόπηκε')->send();
                }),

            Action::make('duplicate')->label('Αντίγραφο')->icon('heroicon-o-document-duplicate')->color('gray')
                ->action(function (Campaign $record) {
                    $copy = $record->replicate(['status', 'scheduled_at', 'started_at', 'finished_at']);
                    $copy->name = $record->name.' (αντίγραφο)';
                    $copy->status = 'draft';
                    $copy->created_by_id = Filament::auth()->id();
                    $copy->save();
                    $this->redirect(static::getResource()::getUrl('edit', ['record' => $copy]));
                }),

            DeleteAction::make()->visible(fn (Campaign $record) => $record->status === 'draft'),
        ];
    }
}
