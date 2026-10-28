<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Μαζική αποστολή προσφορών (newsletter). Φτάνει ΜΟΝΟ σε πελάτες με ρητή συγκατάθεση
 * (άρθρο 11 ν. 3471/2006 · GDPR) και κάθε email έχει διαγραφή με ένα κλικ.
 */
class Campaign extends Model
{
    public const STATUSES = [
        'draft' => 'Πρόχειρο', 'scheduled' => 'Προγραμματισμένη', 'sending' => 'Αποστέλλεται',
        'sent' => 'Στάλθηκε', 'cancelled' => 'Ακυρώθηκε',
    ];

    protected $fillable = ['name', 'subject', 'preheader', 'body', 'package_ids', 'coupon_id', 'cta_label', 'cta_url', 'audience', 'scheduled_at'];

    protected function casts(): array
    {
        return [
            'package_ids' => 'array', 'audience' => 'array',
            'scheduled_at' => 'datetime', 'started_at' => 'datetime', 'finished_at' => 'datetime',
        ];
    }

    public function recipients(): HasMany
    {
        return $this->hasMany(CampaignRecipient::class);
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function isEditable(): bool
    {
        return in_array($this->status, ['draft', 'cancelled'], true);
    }

    /** Πελάτες που επιτρέπεται να λάβουν προσφορές: συγκατάθεση + ενεργός λογαριασμός */
    public static function consenting(): Builder
    {
        return Business::query()
            ->whereNotNull('marketing_consent_at')
            ->whereHas('user', fn ($q) => $q->where('role', 'customer')->whereNotNull('email_verified_at'));
    }

    /** Οι παραλήπτες σύμφωνα με τις επιλογές κοινού */
    public function audienceQuery(): Builder
    {
        $a = (array) $this->audience;
        $q = self::consenting();

        return match ($a['mode'] ?? 'all') {
            'types' => $q->whereIn('business_type', (array) ($a['types'] ?? [])),
            'selected' => $q->whereIn('id', (array) ($a['business_ids'] ?? [])),
            default => $q,
        };
    }

    public function packages()
    {
        return Package::query()->available()->whereIn('id', (array) $this->package_ids)->get();
    }
}
