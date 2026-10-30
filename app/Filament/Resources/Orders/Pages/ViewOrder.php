<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Filament\Resources\Orders\OrderResource;
use App\Filament\Resources\Quotes\QuoteResource;
use App\Models\Order;
use App\Services\Orders\OrderService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;

class ViewOrder extends ViewRecord
{
    protected static string $resource = OrderResource::class;

    public function getTitle(): string
    {
        return 'Παραγγελία '.$this->getRecord()->number;
    }

    public function getSubheading(): ?string
    {
        $o = $this->getRecord();

        return ($o->business?->displayName() ?? $o->user?->email).' · '.$o->money().' · '.$o->stageLabel();
    }

    protected function service(): OrderService
    {
        return app(OrderService::class);
    }

    /** Εκτέλεση με ενιαίο χειρισμό λαθών και ενημέρωση της σελίδας */
    protected function run(callable $fn, string $ok): void
    {
        try {
            $fn($this->getRecord(), Filament::auth()->user());
            Notification::make()->success()->title($ok)->send();
        } catch (InvalidArgumentException $e) {
            Notification::make()->danger()->title($e->getMessage())->send();
        }
        $this->getRecord()->refresh();
    }

    /** Πού πάει μετά την πληρωμή: υλικό από τον πελάτη ή κατευθείαν προετοιμασία */
    protected static function nextSelect(): \Filament\Forms\Components\ToggleButtons
    {
        return \Filament\Forms\Components\ToggleButtons::make('next')->label('Επόμενο βήμα')->inline()->required()
            ->options(['materials' => 'Περιμένουμε υλικό από τον πελάτη', 'production' => 'Έχουμε το υλικό · προετοιμασία'])
            ->default(fn (Order $record) => app(OrderService::class)->afterPayment($record));
    }

    protected static function notifyToggle(): Toggle
    {
        return Toggle::make('notify')->label('Ενημέρωση του πελάτη με email')->default(true);
    }

    protected function getHeaderActions(): array
    {
        $is = fn (array $stages) => fn (Order $record) => in_array($record->stage, $stages, true);

        return [
            Action::make('confirm')->label('Επιβεβαίωση παραγγελίας')->icon('heroicon-o-check-circle')->color('success')
                ->visible($is(['received']))
                ->modalDescription(fn (Order $record) => $record->payment_method === 'credit'
                    ? 'Πληρωμή με πίστωση: η παραγγελία πηγαίνει κατευθείαν σε προετοιμασία.'
                    : 'Ο πελάτης θα δει τα στοιχεία πληρωμής ('.$record->methodLabel().') και την προθεσμία.')
                ->fillForm(fn (Order $record) => ['next' => $this->service()->afterPayment($record), 'notify' => true])
                ->schema(fn (Order $record) => array_values(array_filter([
                    $record->payment_method === 'credit' ? self::nextSelect() : null,
                    Textarea::make('note')->label('Μήνυμα προς τον πελάτη')->rows(3)->placeholder('π.χ. Το σποτ θα το ηχογραφήσουμε εμείς· θα σας καλέσουμε για το κείμενο.'),
                    self::notifyToggle(),
                ])))
                ->action(fn (array $data) => $this->run(fn ($o, $u) => $this->service()->confirm($o, $u, $data['note'] ?? null, (bool) $data['notify'], $data['next'] ?? null), 'Η παραγγελία επιβεβαιώθηκε')),

            Action::make('pay')->label('Επιβεβαίωση πληρωμής')->icon('heroicon-o-banknotes')->color('success')
                ->visible(fn (Order $record) => ! $record->isPaid() && ! $record->isCancelled())
                ->fillForm(fn (Order $record) => ['amount' => $record->balance(), 'next' => $this->service()->afterPayment($record), 'notify' => true])
                ->schema([
                    TextInput::make('amount')->label('Ποσό που λάβαμε')->numeric()->minValue(0.01)->suffix('€')->required()
                        ->helperText(fn (Order $record) => 'Υπόλοιπο: '.$record->money('balance').'. Αν είναι λιγότερο, καταγράφεται ως μερική πληρωμή.'),
                    self::nextSelect()->visible(fn (Order $record) => in_array($record->stage, ['received', 'awaiting_payment', 'payment_review'], true)),
                    Textarea::make('note')->label('Σημείωση προς τον πελάτη')->rows(2)->placeholder('π.χ. Στείλτε μας το λογότυπο και το κείμενο του σποτ.'),
                    self::notifyToggle(),
                ])
                ->action(fn (array $data) => $this->run(fn ($o, $u) => $this->service()->confirmPayment($o, $u, (float) $data['amount'], $data['note'] ?? null, (bool) $data['notify'], $data['next'] ?? null), 'Η πληρωμή καταγράφηκε')),

            Action::make('materials')->label('Ζήτηση υλικού')->icon('heroicon-o-arrow-up-tray')->color('warning')
                ->visible($is(['production', 'scheduled', 'awaiting_payment', 'payment_review', 'received']))
                ->modalDescription('Ο πελάτης βλέπει τη λίστα και ανεβάζει τα αρχεία από τη σελίδα της παραγγελίας.')
                ->schema([
                    Textarea::make('note')->label('Τι χρειαζόμαστε')->rows(4)->required()
                        ->default("• Το σποτ σε MP3/WAV, ή το κείμενο για να το ηχογραφήσουμε εμείς\n• Λογότυπο σε καλή ανάλυση (PNG, SVG ή PDF)\n• Φωτογραφίες ή banner, αν έχετε"),
                    self::notifyToggle(),
                ])
                ->action(fn (array $data) => $this->run(fn ($o, $u) => $this->service()->requestMaterials($o, $u, $data['note'], (bool) $data['notify']), 'Ζητήθηκε το υλικό')),

            Action::make('receipt')->label('Αποδεικτικό κατάθεσης')->icon('heroicon-o-paper-clip')->color('gray')
                ->visible(fn (Order $record) => (bool) $record->receipt_path)
                ->action(fn (Order $record) => Storage::disk('local')->download($record->receipt_path, 'apodeixi-'.preg_replace('/[^0-9-]/', '', (string) $record->number).'.'.pathinfo($record->receipt_path, PATHINFO_EXTENSION))),

            Action::make('reject')->label('Η κατάθεση δεν βρέθηκε')->icon('heroicon-o-exclamation-triangle')->color('warning')
                ->visible($is(['payment_review']))
                ->schema([Textarea::make('reason')->label('Τι να γράψουμε στον πελάτη')->rows(3)->required()
                    ->default('Δεν βλέπουμε ακόμα την κατάθεση στον λογαριασμό μας. Αν την κάνατε από άλλη τράπεζα, μπορεί να χρειαστούν 1–2 εργάσιμες. Αν έχετε αποδεικτικό, ανεβάστε το στη σελίδα της παραγγελίας.')])
                ->action(fn (array $data) => $this->run(fn ($o, $u) => $this->service()->rejectDeposit($o, $u, $data['reason']), 'Ο πελάτης ενημερώθηκε')),

            Action::make('schedule')->label(fn (Order $record) => $record->stage === 'scheduled' ? 'Αλλαγή ημερομηνιών' : 'Προγραμματισμός')
                ->icon('heroicon-o-calendar-days')->color('primary')
                ->visible($is(['received', 'awaiting_payment', 'payment_review', 'materials', 'production', 'scheduled']))
                ->fillForm(fn (Order $record) => [
                    'starts_on' => ($record->starts_on && $record->starts_on->gte(today()) ? $record->starts_on : today()->addDay())->format('Y-m-d'),
                    'ends_on' => ($record->ends_on && $record->ends_on->gte(today()) ? $record->ends_on : today()->addDays(max(1, (int) $record->quote?->days)))->format('Y-m-d'),
                    'notify' => true,
                ])
                ->modalDescription(fn (Order $record) => $record->isPaid() || $record->payment_method === 'credit' ? null : 'Προσοχή: η παραγγελία δεν έχει εξοφληθεί ακόμα.')
                ->schema([
                    DatePicker::make('starts_on')->label('Έναρξη')->native(false)->displayFormat('d/m/Y')->required(),
                    DatePicker::make('ends_on')->label('Λήξη')->native(false)->displayFormat('d/m/Y')->required()->afterOrEqual('starts_on'),
                    Textarea::make('note')->label('Μήνυμα προς τον πελάτη')->rows(2)->placeholder('π.χ. Τα σποτ μεταδίδονται 08:00–20:00 στους σταθμούς της προσφοράς.'),
                    self::notifyToggle(),
                ])
                ->action(fn (array $data) => $this->run(fn ($o, $u) => $this->service()->schedule($o, $u, $data['starts_on'], $data['ends_on'], $data['note'] ?? null, (bool) $data['notify']), 'Ο προγραμματισμός αποθηκεύτηκε')),

            Action::make('zip')->label('Λήψη υλικού (ZIP)')->icon('heroicon-o-archive-box-arrow-down')->color('gray')
                ->visible(fn (Order $record) => $record->storedFiles()->exists())
                ->url(fn (Order $record) => route('admin.order-zip', $record)),

            Action::make('purge')->label('Διαγραφή αρχείων')->icon('heroicon-o-trash')->color('warning')
                ->visible(fn (Order $record) => $record->canPurgeFiles())
                ->modalHeading('Διαγραφή αρχείων υλικού από τον server')
                ->modalDescription(function (Order $record) {
                    $fs = $record->storedFiles()->get();

                    return 'Η καμπάνια έχει τελειώσει. Θα διαγραφούν οριστικά '.$fs->count().' αρχεία ('.\App\Models\OrderFile::make(['size' => $fs->sum('size')])->sizeLabel().'), για να μην πιάνουν χώρο. '
                        .'Το αποδεικτικό κατάθεσης και το τιμολόγιο μένουν. Αν δεν τα έχετε κατεβάσει, κλείστε αυτό το παράθυρο και πατήστε πρώτα «Λήψη υλικού (ZIP)».';
                })
                ->modalSubmitActionLabel('Οριστική διαγραφή')
                ->schema([
                    \Filament\Forms\Components\Checkbox::make('archived')->label('Έχω κατεβάσει και αρχειοθετήσει τα αρχεία σε τοπικό δίσκο')->accepted()
                        ->validationMessages(['accepted' => 'Επιβεβαιώστε ότι τα αρχειοθετήσατε.']),
                ])
                ->action(fn (array $data) => $this->run(fn ($o, $u) => $this->service()->purgeFiles($o, $u, (bool) $data['archived']), 'Τα αρχεία διαγράφηκαν')),

            Action::make('message')->label('Μήνυμα / σημείωση')->icon('heroicon-o-chat-bubble-left-ellipsis')->color('gray')
                ->schema([
                    Textarea::make('text')->label('Κείμενο')->rows(4)->required()->maxLength(3000),
                    Toggle::make('internal')->label('Εσωτερική σημείωση (δεν τη βλέπει ο πελάτης)')->live(),
                    self::notifyToggle()->hidden(fn ($get) => (bool) $get('internal')),
                ])
                ->action(fn (array $data) => $this->run(fn ($o, $u) => $this->service()->note($o, $u, $data['text'], (bool) $data['internal'], (bool) ($data['notify'] ?? false)), 'Καταχωρήθηκε')),

            ActionGroup::make([
                Action::make('sendFile')->label('Αρχείο προς τον πελάτη')->icon('heroicon-o-paper-airplane')
                    ->modalDescription('π.χ. το τελικό σποτ ή το banner για έγκριση. Ο πελάτης το κατεβάζει από τη σελίδα της παραγγελίας.')
                    ->schema(fn (Order $record) => [
                        FileUpload::make('files')->label('Αρχεία')->multiple()->maxFiles(10)->disk('local')->directory('orders/'.$record->ulid.'/yliko')
                            ->visibility('private')->maxSize(Order::FILE_MAX_MB * 1024)->storeFileNamesIn('names')->required(),
                        Textarea::make('note')->label('Μήνυμα')->rows(2)->placeholder('π.χ. Ακούστε το σποτ και πείτε μας αν θέλετε αλλαγές.'),
                        self::notifyToggle(),
                    ])
                    ->action(fn (array $data) => $this->run(fn ($o, $u) => $this->service()->addFiles($o, (array) $data['files'], $u, 'staff', $data['note'] ?? null, (bool) $data['notify'], (array) ($data['names'] ?? [])), 'Τα αρχεία στάλθηκαν')),
                Action::make('invoice')->label('Τιμολόγιο')->icon('heroicon-o-document-text')
                    ->fillForm(fn (Order $record) => ['invoice_number' => $record->invoice_number, 'notify' => true])
                    ->schema(fn (Order $record) => [
                        TextInput::make('invoice_number')->label('Αριθμός τιμολογίου')->required()->maxLength(60),
                        FileUpload::make('invoice_file')->label('PDF τιμολογίου (προαιρετικό)')->disk('local')->directory('orders/'.$record->ulid)
                            ->visibility('private')->acceptedFileTypes(['application/pdf'])->maxSize(10240)
                            ->helperText('Ο πελάτης το κατεβάζει από το Studio του.'),
                        self::notifyToggle(),
                    ])
                    ->action(fn (array $data) => $this->run(fn ($o, $u) => $this->service()->attachInvoice($o, $u, $data['invoice_number'], $data['invoice_file'] ?? null, (bool) $data['notify']), 'Το τιμολόγιο αποθηκεύτηκε')),
                Action::make('stage')->label('Αλλαγή σταδίου')->icon('heroicon-o-arrows-right-left')
                    ->fillForm(fn (Order $record) => ['stage' => $record->stage, 'notify' => true])
                    ->modalDescription('Για εξαιρέσεις. Για τα συνηθισμένα βήματα χρησιμοποιήστε τα κουμπιά πάνω.')
                    ->schema([
                        Select::make('stage')->label('Στάδιο')->options(collect(Order::STAGES)->except('cancelled')->map(fn ($s) => $s['staff'])->all())->required()->native(false),
                        Textarea::make('note')->label('Μήνυμα προς τον πελάτη')->rows(2),
                        self::notifyToggle(),
                    ])
                    ->action(fn (array $data) => $this->run(fn ($o, $u) => $this->service()->setStage($o, $data['stage'], $u, $data['note'] ?? null, (bool) $data['notify']), 'Το στάδιο άλλαξε')),
                Action::make('notes')->label('Εσωτερικές σημειώσεις')->icon('heroicon-o-pencil-square')
                    ->fillForm(fn (Order $record) => ['admin_notes' => $record->admin_notes])
                    ->schema([Textarea::make('admin_notes')->label('')->rows(5)->maxLength(5000)])
                    ->action(function (array $data, Order $record) {
                        $record->forceFill(['admin_notes' => $data['admin_notes']])->save();
                        Notification::make()->success()->title('Αποθηκεύτηκε')->send();
                    }),
                Action::make('quote')->label('Η προσφορά')->icon('heroicon-o-document-magnifying-glass')
                    ->url(fn (Order $record) => $record->quote ? QuoteResource::getUrl('view', ['record' => $record->quote]) : null),
                Action::make('cancel')->label('Ακύρωση παραγγελίας')->icon('heroicon-o-x-circle')->color('danger')
                    ->visible(fn (Order $record) => in_array($record->stage, Order::OPEN, true))
                    ->requiresConfirmation()->modalHeading('Ακύρωση παραγγελίας')
                    ->modalDescription('Η προσφορά λήγει και τυχόν κουπόνι ξαναγίνεται διαθέσιμο στον πελάτη.')
                    ->schema([Textarea::make('reason')->label('Αιτία (τη βλέπει ο πελάτης)')->rows(3)->required(), self::notifyToggle()])
                    ->action(fn (array $data) => $this->run(fn ($o, $u) => $this->service()->cancel($o, $u, $data['reason'], (bool) $data['notify']), 'Η παραγγελία ακυρώθηκε')),
            ])->label('Περισσότερα')->icon('heroicon-o-ellipsis-vertical')->button()->color('gray'),
        ];
    }
}
