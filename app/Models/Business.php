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
        ];
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
