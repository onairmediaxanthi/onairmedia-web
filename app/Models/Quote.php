<?php

namespace App\Models;

use App\Services\Quotes\QuoteCalculator;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Προσφορά πελάτη. Κρατά «φωτογραφία» των τιμών τη στιγμή που φτιάχτηκε (items),
 * ώστε να μην αλλάζει αν αλλάξει ο τιμοκατάλογος μέχρι τη λήξη της.
 */
class Quote extends Model
{
    use HasUlids, SoftDeletes;

    public const STATUSES = [
        'saved' => 'Αποθηκευμένη',
        'submitted' => 'Στάλθηκε για παραγγελία',
        'ordered' => 'Επιβεβαιωμένη παραγγελία',
        'expired' => 'Έληξε',
    ];

    public const SOURCES = ['wizard' => 'Οδηγός', 'manual' => 'Δική του επιλογή', 'package' => 'Έτοιμο πακέτο', 'staff' => 'Από το προσωπικό'];

    protected $fillable = [
        'title', 'intro', 'source', 'price_tier', 'answers', 'items', 'starts_on', 'days', 'subtotal', 'vat_rate', 'vat', 'total',
        'valid_until', 'customer_notes', 'admin_notes',
    ];

    protected function casts(): array
    {
        return [
            'answers' => 'array',
            'items' => 'array',
            'starts_on' => 'date',
            'valid_until' => 'date',
            'subtotal' => 'decimal:2',
            'vat_rate' => 'decimal:2',
            'vat' => 'decimal:2',
            'total' => 'decimal:2',
            'submitted_at' => 'datetime',
            'sent_at' => 'datetime',
            'last_reminded_at' => 'datetime',
            'reminders_off' => 'boolean',
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

    public function order(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Order::class);
    }

    protected static function booted(): void
    {
        static::created(function (Quote $q) {
            $q->forceFill(['number' => 'ΠΡ-'.now()->format('Y').'-'.str_pad((string) $q->id, 4, '0', STR_PAD_LEFT)])->saveQuietly();
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_id');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(CustomerActivity::class);
    }

    public function isExpired(): bool
    {
        return $this->status === 'expired' || ($this->status === 'saved' && $this->valid_until->endOfDay()->isPast());
    }

    public function isEditable(): bool
    {
        return $this->status === 'saved' && ! $this->isExpired();
    }

    public function statusLabel(): string
    {
        return $this->isExpired() ? self::STATUSES['expired'] : (self::STATUSES[$this->status] ?? $this->status);
    }

    /** Γραμμές ομαδοποιημένες ανά μέσο, για εμφάνιση */
    public function groupedItems(): array
    {
        $out = [];
        foreach ((array) $this->items as $line) {
            $out[$line['group'] ?? 'Λοιπά'][] = $line;
        }

        return $out;
    }

    public function specs(): array
    {
        return QuoteCalculator::specsOf((array) $this->items);
    }

    public function money(string $field): string
    {
        return QuoteCalculator::money($this->{$field});
    }
}
