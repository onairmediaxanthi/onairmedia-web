<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Business extends Model
{
    /** Είδη επιχείρησης (για στόχευση προσφορών) */
    public const TYPES = [
        'retail' => 'Κατάστημα λιανικής',
        'food' => 'Εστίαση (καφέ, εστιατόριο, μπαρ)',
        'hotel' => 'Τουρισμός & διαμονή',
        'health' => 'Υγεία & ομορφιά',
        'auto' => 'Αυτοκίνητο & συνεργεία',
        'construction' => 'Κατασκευές & ακίνητα',
        'professional' => 'Ελεύθερο επάγγελμα & υπηρεσίες',
        'education' => 'Εκπαίδευση & φροντιστήρια',
        'industry' => 'Παραγωγή & βιομηχανία',
        'events' => 'Εκδηλώσεις & ψυχαγωγία',
        'public' => 'Φορέας / Δήμος / Σύλλογος',
        'other' => 'Άλλο',
    ];

    public const TERMS_VERSION = '2026-10';

    protected $fillable = [
        'legal_name', 'trade_name', 'business_type', 'business_type_other', 'vat', 'tax_office', 'gemi',
        'address', 'city', 'postal_code', 'mobile', 'phone', 'contact_person', 'website',
        // Μόνο από τη διαχείριση· οι φόρμες πελατών περνούν μόνο τα δικά τους πεδία
        'admin_notes',
    ];

    protected function casts(): array
    {
        return [
            'vat_verified_at' => 'datetime',
            'terms_accepted_at' => 'datetime',
            'marketing_consent_at' => 'datetime',
            'invited_at' => 'datetime',
        ];
    }

    public const SOURCES = ['self' => 'Εγγραφή από το site', 'staff' => 'Από τη διαχείριση'];

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function isStaffCreated(): bool
    {
        return $this->source === 'staff';
    }

    /** Ο λογαριασμός ενεργοποιείται όταν επιβεβαιωθεί το email (εγγραφή ή πρόσκληση) */
    public function isActivated(): bool
    {
        return $this->user?->email_verified_at !== null;
    }

    public function sourceLabel(): string
    {
        return $this->isStaffCreated()
            ? 'Από τη διαχείριση'.($this->createdBy ? ' · '.$this->createdBy->name : '')
            : 'Εγγραφή από το site';
    }

    /** Ό,τι έκανε ο πελάτης (μέσω του λογαριασμού του) */
    public function activities(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(CustomerActivity::class, 'user_id', 'user_id');
    }

    public function quotes(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Quote::class, 'user_id', 'user_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function displayName(): string
    {
        return $this->trade_name ?: $this->legal_name;
    }

    public function typeLabel(): string
    {
        return $this->business_type === 'other'
            ? ($this->business_type_other ?: self::TYPES['other'])
            : (self::TYPES[$this->business_type] ?? $this->business_type);
    }
}
