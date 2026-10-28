<?php

namespace App\Filament\Resources\Quotes\Pages;

use App\Filament\Resources\Quotes\QuoteResource;
use App\Mail\QuoteProposal;
use App\Models\Quote;
use App\Services\CustomerEvents;
use App\Services\Quotes\QuotePdf;
use App\Services\Quotes\QuotePresenter;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Mail;
use Throwable;

class ViewQuote extends ViewRecord
{
    protected static string $resource = QuoteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('send')->label(fn (Quote $record) => $record->sent_at ? 'Νέα αποστολή' : 'Αποστολή στον πελάτη')
                ->icon('heroicon-o-paper-airplane')
                ->hidden(fn (Quote $record) => $record->trashed() || ! $record->user)
                ->modalHeading('Αποστολή πρότασης στον πελάτη')
                ->modalDescription(fn (Quote $record) => 'Προς: '.$record->user?->email.($record->user?->business?->isActivated() ? '' : ' · ο πελάτης θα ενεργοποιήσει τον λογαριασμό του από το ίδιο email'))
                ->modalSubmitActionLabel('Αποστολή')
                ->fillForm(fn (Quote $record) => [
                    'subject' => 'Πρόταση προβολής '.$record->number.' · '.\App\Models\Setting::get('company_name'),
                    'message' => QuotePresenter::intro($record),
                    'attach' => true,
                ])
                ->schema([
                    TextInput::make('subject')->label('Θέμα')->required()->maxLength(150),
                    Textarea::make('message')->label('Μήνυμα')->required()->rows(7)->maxLength(3000)
                        ->helperText('Μπαίνει και στην αρχή του PDF.'),
                    Toggle::make('attach')->label('Συνημμένο PDF'),
                ])
                ->action(function (Quote $record, array $data) {
                    $record->intro = $data['message'];
                    $record->save();
                    try {
                        Mail::to($record->user->email)->send(new QuoteProposal($record, $data['subject'], $data['message'], (bool) $data['attach'], Filament::auth()->user()));
                    } catch (Throwable $e) {
                        report($e);
                        Notification::make()->danger()->title('Η αποστολή απέτυχε')->body('Ελέγξτε τις ρυθμίσεις email και δοκιμάστε ξανά.')->send();

                        return;
                    }
                    $record->forceFill(['sent_at' => now(), 'sent_count' => $record->sent_count + 1])->save();
                    if ($b = $record->user->business) {
                        $b->invited_at ??= $b->isActivated() ? null : now();
                        $b->save();
                    }
                    CustomerEvents::record('quote_sent', $record->user, $record, ['by' => Filament::auth()->user()?->name]);
                    Notification::make()->success()->title('Η πρόταση στάλθηκε στο '.$record->user->email)->send();
                }),
            \Filament\Actions\EditAction::make()->label('Αλλαγές')->icon('heroicon-o-adjustments-horizontal')->color('gray')
                ->hidden(fn (Quote $record) => $record->trashed()),
            Action::make('pdf')->label('PDF')->icon('heroicon-o-arrow-down-tray')->color('gray')
                ->action(fn (Quote $record) => response()->streamDownload(fn () => print (QuotePdf::render($record)), QuotePdf::filename($record))),
            Action::make('update')->label('Κατάσταση')->icon('heroicon-o-pencil-square')->color('gray')
                ->hidden(fn (Quote $record) => $record->trashed())
                ->fillForm(fn (Quote $record) => ['status' => $record->status, 'admin_notes' => $record->admin_notes])
                ->schema([
                    Select::make('status')->label('Κατάσταση')->options(Quote::STATUSES)->required()->native(false),
                    Textarea::make('admin_notes')->label('Εσωτερικές σημειώσεις')->rows(4)->maxLength(5000)
                        ->helperText('Δεν τις βλέπει ο πελάτης.'),
                ])
                ->action(function (Quote $record, array $data) {
                    $record->status = $data['status'];
                    $record->admin_notes = $data['admin_notes'];
                    $record->save();
                    Notification::make()->success()->title('Αποθηκεύτηκε')->send();
                }),
        ];
    }
}
