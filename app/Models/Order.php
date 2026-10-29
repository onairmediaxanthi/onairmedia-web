<?php

namespace App\Models;

use App\Services\Quotes\QuoteCalculator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Παραγγελία: ό,τι έστειλε ο πελάτης από το ταμείο, από την παραλαβή ως την ολοκλήρωση της καμπάνιας */
class Order extends Model
{
    use HasUlids;

    /**
     * Τα στάδια με τη σειρά τους. 'staff' = όπως το βλέπει η ομάδα, 'customer' = όπως το βλέπει ο πελάτης,
     * 'text' = τι σημαίνει για τον πελάτη, 'step' = σε ποιο βήμα της μπάρας προόδου ανήκει.
     */
    public const STAGES = [
        'received' => ['staff' => 'Νέα · προς επιβεβαίωση', 'customer' => 'Λάβαμε την παραγγελία', 'color' => 'warning', 'icon' => 'heroicon-o-inbox-arrow-down', 'step' => 0,
            'text' => 'Ελέγχουμε τη διαθεσιμότητα και το πρόγραμμα. Θα σας επιβεβαιώσουμε εντός μίας εργάσιμης.'],
        'awaiting_payment' => ['staff' => 'Αναμονή πληρωμής', 'customer' => 'Αναμονή πληρωμής', 'color' => 'info', 'icon' => 'heroicon-o-banknotes', 'step' => 1,
            'text' => 'Η παραγγελία επιβεβαιώθηκε. Μόλις λάβουμε την πληρωμή, ξεκινάμε την προετοιμασία.'],
        'payment_review' => ['staff' => 'Έλεγχος κατάθεσης', 'customer' => 'Ελέγχουμε την κατάθεσή σας', 'color' => 'danger', 'icon' => 'heroicon-o-magnifying-glass', 'step' => 1,
            'text' => 'Λάβαμε τη δήλωση κατάθεσης. Μόλις τη δούμε στον λογαριασμό μας, σας ενημερώνουμε.'],
        'materials' => ['staff' => 'Αναμονή υλικού από πελάτη', 'customer' => 'Περιμένουμε το υλικό σας', 'color' => 'warning', 'icon' => 'heroicon-o-arrow-up-tray', 'step' => 2,
            'text' => 'Ανεβάστε εδώ ό,τι χρειάζεται η καμπάνια: σποτ ή κείμενο για ηχογράφηση, λογότυπο, φωτογραφίες, banner. Μόλις τα ανεβάσετε, πατήστε «Έστειλα όλο το υλικό».'],
        'production' => ['staff' => 'Προετοιμασία', 'customer' => 'Σε προετοιμασία', 'color' => 'primary', 'icon' => 'heroicon-o-musical-note', 'step' => 3,
            'text' => 'Ετοιμάζουμε το υλικό της καμπάνιας (σποτ, banner, γραφικό) και το πρόγραμμα μετάδοσης.'],
        'scheduled' => ['staff' => 'Προγραμματίστηκε', 'customer' => 'Προγραμματίστηκε', 'color' => 'primary', 'icon' => 'heroicon-o-calendar-days', 'step' => 4,
            'text' => 'Όλα είναι έτοιμα: η καμπάνια σας είναι στο πρόγραμμα.'],
        'running' => ['staff' => 'Σε προβολή', 'customer' => 'Σε προβολή', 'color' => 'success', 'icon' => 'heroicon-o-signal', 'step' => 5,
            'text' => 'Η καμπάνια σας ακούγεται και φαίνεται τώρα.'],
        'completed' => ['staff' => 'Ολοκληρώθηκε', 'customer' => 'Ολοκληρώθηκε', 'color' => 'gray', 'icon' => 'heroicon-o-check-badge', 'step' => 6,
            'text' => 'Η καμπάνια ολοκληρώθηκε. Ευχαριστούμε που μας εμπιστευτήκατε!'],
        'cancelled' => ['staff' => 'Ακυρώθηκε', 'customer' => 'Ακυρώθηκε', 'color' => 'gray', 'icon' => 'heroicon-o-x-circle', 'step' => -1,
            'text' => 'Η παραγγελία ακυρώθηκε.'],
    ];

    /** Τα βήματα της μπάρας προόδου που βλέπει ο πελάτης */
    public const STEPS = ['Παραλαβή', 'Πληρωμή', 'Υλικό', 'Προετοιμασία', 'Πρόγραμμα', 'Σε προβολή', 'Ολοκλήρωση'];

    public const METHODS = [
        'bank' => 'Τραπεζική κατάθεση',
        'office' => 'Στο γραφείο μας (μετρητά ή κάρτα)',
        'credit' => 'Με πίστωση, βάσει τιμολογίου',
    ];

    /** Στάδια που περιμένουν κάποια ενέργεια από την ομάδα */
    public const NEEDS_ACTION = ['received', 'payment_review'];

    public const OPEN = ['received', 'awaiting_payment', 'payment_review', 'materials', 'production', 'scheduled', 'running'];

    /** Τι δέχεται το ανέβασμα υλικού (ήχος, εικόνα, βίντεο, έγγραφα, αρχεία γραφιστικών) */
    public const FILE_TYPES = ['mp3', 'wav', 'm4a', 'aac', 'ogg', 'flac', 'jpg', 'jpeg', 'png', 'webp', 'gif', 'svg', 'mp4', 'mov',
        'pdf', 'doc', 'docx', 'txt', 'rtf', 'odt', 'zip', 'ai', 'psd', 'eps'];

    public const FILE_MAX_MB = 50;

    /** Σύνολο ανά αποστολή: κάτω από το post_max_size του server (130 MB) */
    public const BATCH_MAX_MB = 120;

    protected $fillable = ['admin_notes', 'invoice_number'];

    protected function casts(): array
    {
        return [
            'total' => 'decimal:2', 'paid_amount' => 'decimal:2',
            'paid_at' => 'datetime', 'deposit_declared_at' => 'datetime',
            'payment_due_on' => 'date', 'starts_on' => 'date', 'ends_on' => 'date',
        ];
    }

    public function uniqueIds(): array
    {
        return ['ulid'];
    }

    public function getRouteKeyName(): string
    {
        return 'ulid';
    }

    protected static function booted(): void
    {
        static::created(function (Order $o) {
            $o->forceFill(['number' => 'ΠΑΡ-'.now()->format('Y').'-'.str_pad((string) $o->id, 4, '0', STR_PAD_LEFT)])->saveQuietly();
        });
    }

    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class)->withTrashed();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(OrderEvent::class)->orderByDesc('created_at')->orderByDesc('id');
    }

    public function files(): HasMany
    {
        return $this->hasMany(OrderFile::class)->latest();
    }

    /** Ο πελάτης μπορεί να ανεβάζει υλικό όσο η παραγγελία είναι ανοιχτή και πριν τελειώσει η καμπάνια */
    public function acceptsFiles(): bool
    {
        return in_array($this->stage, ['received', 'awaiting_payment', 'payment_review', 'materials', 'production', 'scheduled'], true);
    }

    public function scopeOpen(Builder $q): Builder
    {
        return $q->whereIn('stage', self::OPEN);
    }

    public function stageInfo(): array
    {
        return self::STAGES[$this->stage] ?? self::STAGES['received'];
    }

    public function stageLabel(bool $customer = false): string
    {
        return $this->stageInfo()[$customer ? 'customer' : 'staff'];
    }

    public function step(): int
    {
        return $this->stageInfo()['step'];
    }

    public function methodLabel(): string
    {
        return self::METHODS[$this->payment_method] ?? $this->payment_method;
    }

    public function isPaid(): bool
    {
        return $this->paid_at !== null;
    }

    public function balance(): float
    {
        return max(0, round((float) $this->total - (float) $this->paid_amount, 2));
    }

    public function money(string $field = 'total'): string
    {
        return QuoteCalculator::money((float) ($field === 'balance' ? $this->balance() : $this->{$field}));
    }

    /** Ο πελάτης μπορεί να δηλώσει κατάθεση */
    public function canDeclareDeposit(): bool
    {
        return $this->stage === 'awaiting_payment' && $this->payment_method === 'bank' && ! $this->isPaid();
    }

    public function isCancelled(): bool
    {
        return $this->stage === 'cancelled';
    }
}
