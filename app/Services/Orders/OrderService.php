<?php

namespace App\Services\Orders;

use App\Mail\OrderUpdated;
use App\Models\Order;
use App\Models\OrderEvent;
use App\Models\Quote;
use App\Models\Setting;
use App\Models\User;
use App\Services\Coupons;
use App\Services\CustomerEvents;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Throwable;

/**
 * Όλες οι αλλαγές μιας παραγγελίας περνούν από εδώ: κάθε βήμα γράφεται στο ιστορικό
 * και, όπου αφορά τον πελάτη, του πηγαίνει email με το νέο στάδιο.
 */
class OrderService
{
    /** Από το ταμείο: η προσφορά γίνεται παραγγελία */
    public function createFromQuote(Quote $quote, string $method): Order
    {
        if (! in_array($method, self::methodsFor($quote->user), true)) {
            throw new InvalidArgumentException('Ο τρόπος πληρωμής δεν είναι διαθέσιμος.');
        }

        return DB::transaction(function () use ($quote, $method) {
            $order = new Order;
            $order->forceFill([
                'quote_id' => $quote->id, 'user_id' => $quote->user_id, 'business_id' => $quote->user?->business?->id,
                'stage' => 'received', 'payment_method' => $method, 'total' => $quote->total,
                'starts_on' => $quote->starts_on,
                'ends_on' => $quote->starts_on && $quote->days ? $quote->starts_on->copy()->addDays($quote->days - 1) : null,
            ])->save();
            $this->log($order, 'received', 'Λάβαμε την παραγγελία', $quote->customer_notes ? 'Σημειώσεις πελάτη: '.$quote->customer_notes : null, internal: false);

            return $order;
        });
    }

    /** @return list<string> */
    public static function methodsFor(?User $user): array
    {
        $m = ['bank'];
        if (Setting::get('payment_office_enabled', true)) {
            $m[] = 'office';
        }
        if ($user?->business?->credit_allowed) {
            $m[] = 'credit';
        }

        return $m;
    }

    /** Η ομάδα επιβεβαιώνει διαθεσιμότητα και πρόγραμμα */
    public function confirm(Order $order, ?User $staff, ?string $note = null, bool $notify = true, ?string $next = null): Order
    {
        $this->expect($order, ['received']);
        $order->quote?->forceFill(['status' => 'ordered'])->save();

        if ($order->payment_method === 'credit') {
            return $this->move($order, $this->afterPayment($order, $next), 'Η παραγγελία επιβεβαιώθηκε', $note, $staff, $notify);
        }
        $order->payment_due_on = today()->addDays(max(1, (int) Setting::get('payment_due_days', 3)));

        return $this->move($order, 'awaiting_payment', 'Η παραγγελία επιβεβαιώθηκε', $note, $staff, $notify);
    }

    /** Ο πελάτης δηλώνει ότι έκανε την κατάθεση (προαιρετικά με αποδεικτικό) */
    public function declareDeposit(Order $order, ?string $note, ?UploadedFile $receipt): Order
    {
        if (! $order->canDeclareDeposit()) {
            throw new InvalidArgumentException('Η παραγγελία δεν περιμένει κατάθεση.');
        }
        if ($receipt) {
            if ($order->receipt_path) {
                Storage::disk('local')->delete($order->receipt_path);
            }
            $order->receipt_path = $receipt->store('orders/'.$order->ulid, 'local');
        }
        $order->deposit_declared_at = now();
        $order->deposit_note = $note ? mb_substr(trim($note), 0, 500) : null;
        $this->move($order, 'payment_review', 'Δηλώσατε την κατάθεση', $order->deposit_note, null, notify: false);
        CustomerEvents::record('deposit_declared', $order->user, $order->quote, ['order' => $order->number]);

        return $order;
    }

    /** Η ομάδα βλέπει τα χρήματα: εξόφληση (ή μερική πληρωμή) */
    /** Μετά την πληρωμή: υλικό από τον πελάτη, ή κατευθείαν προετοιμασία αν το έχουμε ήδη */
    public function afterPayment(Order $order, ?string $next = null): string
    {
        if (in_array($next, ['materials', 'production'], true)) {
            return $next;
        }

        return $order->files()->where('from', 'customer')->exists() ? 'production' : 'materials';
    }

    public function confirmPayment(Order $order, ?User $staff, ?float $amount = null, ?string $note = null, bool $notify = true, ?string $next = null): Order
    {
        $this->expect($order, ['received', 'awaiting_payment', 'payment_review', 'materials', 'production', 'scheduled', 'running', 'completed']);
        $amount = $amount === null ? $order->balance() : round($amount, 2);
        if ($amount <= 0) {
            throw new InvalidArgumentException('Γράψτε ποσό πληρωμής.');
        }
        $order->paid_amount = round((float) $order->paid_amount + $amount, 2);
        $full = $order->balance() <= 0.0;
        if ($full) {
            $order->paid_at = now();
        }
        $title = $full ? 'Η πληρωμή επιβεβαιώθηκε' : 'Λάβαμε μέρος της πληρωμής ('.\App\Services\Quotes\QuoteCalculator::money($amount).')';
        if ($full && in_array($order->stage, ['received', 'awaiting_payment', 'payment_review'], true)) {
            if ($order->quote && $order->quote->status !== 'ordered') {
                $order->quote->forceFill(['status' => 'ordered'])->save();
            }

            return $this->move($order, $this->afterPayment($order, $next), $title, $note, $staff, $notify);
        }
        if (! $full && $order->stage === 'payment_review') {
            return $this->move($order, 'awaiting_payment', $title, $note ?: 'Υπόλοιπο: '.$order->money('balance'), $staff, $notify);
        }
        $order->save();
        $this->log($order, null, $title, $note, $staff, internal: false, notify: $notify);

        return $order;
    }

    /** Η κατάθεση δεν βρέθηκε: πίσω σε αναμονή πληρωμής, με εξήγηση προς τον πελάτη */
    public function rejectDeposit(Order $order, ?User $staff, string $reason): Order
    {
        $this->expect($order, ['payment_review']);

        return $this->move($order, 'awaiting_payment', 'Δεν βρήκαμε ακόμα την κατάθεση', $reason, $staff, true);
    }

    public function schedule(Order $order, ?User $staff, Carbon|string $starts, Carbon|string $ends, ?string $note = null, bool $notify = true): Order
    {
        $this->expect($order, ['received', 'awaiting_payment', 'payment_review', 'materials', 'production', 'scheduled']);
        $starts = Carbon::parse($starts)->startOfDay();
        $ends = Carbon::parse($ends)->startOfDay();
        if ($ends->lt($starts)) {
            throw new InvalidArgumentException('Η λήξη δεν μπορεί να είναι πριν από την έναρξη.');
        }
        $order->starts_on = $starts;
        $order->ends_on = $ends;
        $title = 'Προγραμματίστηκε: '.$starts->format('d/m/Y').' έως '.$ends->format('d/m/Y');

        // Αν η έναρξη είναι σήμερα ή πέρασε, πάει κατευθείαν σε προβολή
        return $this->move($order, $starts->lte(today()) ? 'running' : 'scheduled', $title, $note, $staff, $notify);
    }

    /** Η ομάδα ζητά υλικό από τον πελάτη (με λίστα του τι χρειάζεται) */
    public function requestMaterials(Order $order, ?User $staff, ?string $note, bool $notify = true): Order
    {
        $this->expect($order, ['received', 'awaiting_payment', 'payment_review', 'production', 'scheduled', 'materials']);

        return $this->move($order, 'materials', 'Χρειαζόμαστε το υλικό σας', $note, $staff, $notify);
    }

    /**
     * Ανέβασμα υλικού. Από τον πελάτη: ειδοποίηση της ομάδας. Από την ομάδα (π.χ. το τελικό σποτ για έγκριση):
     * προαιρετικά email στον πελάτη.
     *
     * @param  list<UploadedFile|string>  $files  αρχεία ή ήδη αποθηκευμένα paths (από τη διαχείριση)
     * @param  array<string, string>  $names  path => αρχικό όνομα, για τα αποθηκευμένα
     */
    public function addFiles(Order $order, array $files, ?User $by, string $from = 'customer', ?string $note = null, bool $notify = false, array $names = [], ?string $slot = null): int
    {
        if ($from === 'customer' && ! $order->acceptsFiles()) {
            throw new InvalidArgumentException('Η παραγγελία δεν δέχεται πια αρχεία.');
        }
        $plan = MaterialPlan::for($order);
        $box = $slot !== null ? ($plan[$slot] ?? null) : null;
        if ($from === 'customer' && ! $box) {
            throw new InvalidArgumentException('Διαλέξτε για ποιο μέσο είναι το αρχείο.');
        }

        // Πρώτα ελέγχονται όλα· αν ένα δεν ταιριάζει, δεν αποθηκεύεται κανένα (και ο πελάτης βλέπει γιατί)
        $metas = [];
        if ($box) {
            $existing = $order->files()->where('slot', $slot)->whereNull('purged_at')->count();
            if ($from === 'customer' && $existing + count($files) > $box['max_files']) {
                throw new InvalidArgumentException('Έως '.$box['max_files'].' αρχεία για «'.$box['title'].'». Σβήστε κάποιο πριν ανεβάσετε άλλο.');
            }
            $errors = [];
            foreach ($files as $i => $f) {
                if ($f instanceof UploadedFile) {
                    try {
                        $metas[$i] = MaterialPlan::check($box, $f);
                    } catch (InvalidArgumentException $e) {
                        $errors[] = $e->getMessage();
                    }
                }
            }
            if ($errors) {
                throw new InvalidArgumentException(implode(' ', $errors));
            }
        }

        $added = [];
        $warnings = [];
        foreach ($files as $i => $f) {
            if ($f instanceof UploadedFile) {
                $path = $f->store('orders/'.$order->ulid.'/yliko', 'local');
                $name = mb_substr(preg_replace('/[^\pL\pN ._()-]/u', '_', $f->getClientOriginalName()), 0, 180);
                $size = (int) $f->getSize();
                $meta = $metas[$i] ?? \App\Support\FileInspector::inspect($f->getRealPath(), $f->getClientOriginalExtension());
            } else {
                $path = $f;
                $name = mb_substr($names[$f] ?? basename($f), 0, 180);
                $size = (int) (Storage::disk('local')->size($f) ?: 0);
                $meta = \App\Support\FileInspector::inspect(Storage::disk('local')->path($f), pathinfo($name, PATHINFO_EXTENSION));
            }
            $order->files()->create(['path' => $path, 'name' => $name, 'size' => $size, 'from' => $from, 'user_id' => $by?->id, 'slot' => $slot, 'meta' => $meta ?: null]);
            $added[] = $name;
            foreach ((array) ($meta['warnings'] ?? []) as $w) {
                $warnings[] = $name.': '.$w;
            }
        }
        if (! $added) {
            return 0;
        }
        $list = ($box ? $box['title'].': ' : '').implode(', ', array_slice($added, 0, 6)).(count($added) > 6 ? ' και άλλα '.(count($added) - 6) : '');
        $body = trim($list."\n".implode("\n", $warnings)."\n".($note ?? ''));
        if ($from === 'customer') {
            $this->log($order, null, count($added) === 1 ? 'Ανεβάσατε 1 αρχείο' : 'Ανεβάσατε '.count($added).' αρχεία', $body);
            CustomerEvents::record('files_uploaded', $order->user, $order->quote, ['order' => $order->number, 'files' => count($added), 'slot' => $slot]);
        } else {
            $this->log($order, null, count($added) === 1 ? 'Σας στείλαμε 1 αρχείο' : 'Σας στείλαμε '.count($added).' αρχεία', $body, $by, false, $notify);
        }

        return count($added);
    }

    /** Ο πελάτης σβήνει δικό του αρχείο (πριν ξεκινήσει η καμπάνια), π.χ. για να ανεβάσει σωστό */
    public function removeFile(Order $order, \App\Models\OrderFile $file): void
    {
        if ($file->order_id !== $order->id || $file->from !== 'customer' || ! in_array($order->stage, ['received', 'awaiting_payment', 'payment_review', 'materials'], true)) {
            throw new InvalidArgumentException('Το αρχείο δεν μπορεί πια να αφαιρεθεί. Επικοινωνήστε μαζί μας.');
        }
        Storage::disk('local')->delete($file->path);
        $file->delete();
        $this->log($order, null, 'Αφαιρέσατε το αρχείο '.$file->name, null);
    }

    /** Κείμενο πελάτη για ένα κουτί (κείμενο σποτ, σύνδεσμος banner…) */
    public function saveBrief(Order $order, string $slot, ?string $text): Order
    {
        if (! $order->acceptsFiles() || ! isset(MaterialPlan::for($order)[$slot])) {
            throw new InvalidArgumentException('Δεν γίνεται πια αλλαγή.');
        }
        $brief = (array) ($order->brief ?? []);
        $text = trim((string) $text);
        $before = $brief[$slot] ?? null;
        if ($text === '') {
            unset($brief[$slot]);
        } else {
            $brief[$slot] = mb_substr($text, 0, 3000);
        }
        $order->brief = $brief ?: null;
        $order->save();
        if ($text !== '' && $text !== $before) {
            $this->log($order, null, 'Στείλατε κείμενο: '.(MaterialPlan::for($order)[$slot]['title'] ?? $slot), mb_strimwidth($text, 0, 400, '…'));
        }

        return $order;
    }

    /** Ο πελάτης δηλώνει ότι έστειλε όλο το υλικό */
    public function materialsDone(Order $order, ?string $note = null): Order
    {
        $this->expect($order, ['materials']);
        if (! MaterialPlan::complete($order) && blank($note)) {
            $missing = collect(MaterialPlan::status($order))->filter(fn ($s) => $s['state'] === 'missing')->keys()
                ->map(fn ($k) => MaterialPlan::for($order)[$k]['title'])->implode(', ');
            throw new InvalidArgumentException('Λείπει ακόμα: '.$missing.'. Αν θα τα στείλετε αλλιώς, γράψτε μας πώς στο πεδίο σημείωσης.');
        }
        $this->move($order, 'production', 'Στείλατε όλο το υλικό', $note, null, notify: false);
        CustomerEvents::record('materials_done', $order->user, $order->quote, ['order' => $order->number]);

        return $order;
    }

    /**
     * Μετά τη λήξη: σβήνει από τον server τα αρχεία υλικού (όχι αποδεικτικό κατάθεσης και τιμολόγιο, που είναι λογιστικά).
     * Μόνο αφού το προσωπικό επιβεβαιώσει ότι τα αρχειοθέτησε.
     */
    public function purgeFiles(Order $order, ?User $staff, bool $archived): int
    {
        if (! $archived) {
            throw new InvalidArgumentException('Επιβεβαιώστε πρώτα ότι τα αρχεία έχουν αρχειοθετηθεί.');
        }
        if (! in_array($order->stage, ['completed', 'cancelled'], true)) {
            throw new InvalidArgumentException('Τα αρχεία σβήνονται μόνο μετά τη λήξη ή την ακύρωση της καμπάνιας.');
        }
        $files = $order->storedFiles()->get();
        $bytes = 0;
        foreach ($files as $f) {
            Storage::disk('local')->delete($f->path);
            $bytes += (int) $f->size;
            $f->forceFill(['purged_at' => now()])->save();
        }
        $order->forceFill(['files_purged_at' => now()])->save();
        $this->log($order, null, 'Διαγράφηκαν '.$files->count().' αρχεία υλικού ('.\App\Models\OrderFile::make(['size' => $bytes])->sizeLabel().')',
            'Το προσωπικό επιβεβαίωσε ότι αρχειοθετήθηκαν σε τοπικό δίσκο.', $staff, internal: true);

        return $files->count();
    }

    public function cancel(Order $order, ?User $staff, string $reason, bool $notify = true): Order
    {
        $this->expect($order, Order::OPEN);
        $order->cancel_reason = mb_substr($reason, 0, 500);
        if ($quote = $order->quote) {
            $quote->forceFill(['status' => 'expired'])->save();
            app(Coupons::class)->release($quote); // το κουπόνι ξαναγίνεται διαθέσιμο
        }

        return $this->move($order, 'cancelled', 'Η παραγγελία ακυρώθηκε', $reason, $staff, $notify);
    }

    /** Ελεύθερη αλλαγή σταδίου από τη διαχείριση (για εξαιρέσεις) */
    public function setStage(Order $order, string $stage, ?User $staff, ?string $note, bool $notify): Order
    {
        if (! isset(Order::STAGES[$stage])) {
            throw new InvalidArgumentException('Άγνωστο στάδιο.');
        }

        $this->move($order, $stage, Order::STAGES[$stage]['customer'], $note, $staff, $notify);
        if ($stage === 'completed') {
            $this->remindPurge($order);
        }

        return $order;
    }

    /** Μήνυμα προς τον πελάτη ή εσωτερική σημείωση, χωρίς αλλαγή σταδίου */
    public function note(Order $order, ?User $staff, string $text, bool $internal, bool $notify): OrderEvent
    {
        return $this->log($order, null, $internal ? 'Εσωτερική σημείωση' : 'Μήνυμα από την ομάδα μας', $text, $staff, $internal, ! $internal && $notify);
    }

    public function attachInvoice(Order $order, ?User $staff, string $number, ?string $path, bool $notify = true): Order
    {
        if ($order->invoice_path && $path && $order->invoice_path !== $path) {
            Storage::disk('local')->delete($order->invoice_path);
        }
        $order->invoice_number = mb_substr(trim($number), 0, 60);
        if ($path) {
            $order->invoice_path = $path;
        }
        $order->save();
        $this->log($order, null, 'Εκδόθηκε τιμολόγιο '.$order->invoice_number, null, $staff, false, $notify);

        return $order;
    }

    /** Καθημερινά: έναρξη και λήξη καμπανιών με βάση τις ημερομηνίες */
    public function advanceByDate(): array
    {
        $started = $finished = 0;
        foreach (Order::query()->where('stage', 'scheduled')->whereDate('starts_on', '<=', today())->get() as $o) {
            $this->move($o, 'running', 'Η καμπάνια σας ξεκίνησε', null, null, true);
            $started++;
        }
        foreach (Order::query()->where('stage', 'running')->whereNotNull('ends_on')->whereDate('ends_on', '<', today())->get() as $o) {
            $this->move($o, 'completed', 'Η καμπάνια ολοκληρώθηκε', null, null, true);
            $this->remindPurge($o);
            $finished++;
        }

        return [$started, $finished];
    }

    /** Στη λήξη: υπενθύμιση στο προσωπικό να αρχειοθετήσει και να σβήσει τα αρχεία */
    public function remindPurge(Order $order): void
    {
        $count = $order->storedFiles()->count();
        if (! $count) {
            return;
        }
        try {
            $staff = User::query()->whereIn('role', [\App\Enums\UserRole::Admin->value, \App\Enums\UserRole::Sales->value])->whereNull('disabled_at')->get();
            \Filament\Notifications\Notification::make()->title('Ολοκληρώθηκε: '.$order->number)
                ->body($count.' αρχεία υλικού πιάνουν χώρο. Κατεβάστε τα (ZIP) και διαγράψτε τα από τον server.')
                ->icon('heroicon-o-archive-box-arrow-down')->iconColor('warning')
                ->actions([\Filament\Actions\Action::make('open')->label('Άνοιγμα')->url(\App\Filament\Resources\Orders\OrderResource::getUrl('view', ['record' => $order], panel: 'admin'))->markAsRead()])
                ->sendToDatabase($staff);
        } catch (Throwable $e) {
            Log::warning('Purge reminder failed: '.$e->getMessage());
        }
    }

    protected function move(Order $order, string $stage, string $title, ?string $note, ?User $staff, bool $notify): Order
    {
        $order->stage = $stage;
        $order->save();
        $this->log($order, $stage, $title, $note, $staff, internal: false, notify: $notify);
        CustomerEvents::record('order_stage', $order->user, $order->quote, ['order' => $order->number, 'stage' => $stage]);

        return $order;
    }

    protected function log(Order $order, ?string $stage, string $title, ?string $note, ?User $staff = null, bool $internal = false, bool $notify = false): OrderEvent
    {
        $event = $order->events()->create([
            'stage' => $stage, 'title' => mb_substr($title, 0, 255), 'note' => $note ? trim($note) : null,
            'internal' => $internal, 'notified' => false, 'user_id' => $staff?->id,
        ]);
        if ($notify && ! $internal && $order->user?->email) {
            try {
                Mail::to($order->user->email)->send(new OrderUpdated($order, $event));
                $event->forceFill(['notified' => true])->save();
            } catch (Throwable $e) {
                Log::warning('Order email failed: '.$e->getMessage());
            }
        }

        return $event;
    }

    protected function expect(Order $order, array $stages): void
    {
        if (! in_array($order->stage, $stages, true)) {
            throw new InvalidArgumentException('Δεν γίνεται σε αυτό το στάδιο ('.$order->stageLabel().').');
        }
    }
}
