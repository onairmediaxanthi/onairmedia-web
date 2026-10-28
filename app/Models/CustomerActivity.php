<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Ημερολόγιο ενεργειών πελατών, για ενημέρωση της εταιρείας */
class CustomerActivity extends Model
{
    public const UPDATED_AT = null;

    public const TYPES = [
        'registered' => 'Νέα εγγραφή',
        'verified' => 'Επιβεβαίωσε email',
        'quote_saved' => 'Αποθήκευσε προσφορά',
        'quote_pdf' => 'Κατέβασε PDF',
        'quote_submitted' => 'Προχώρησε στο ταμείο',
        'quote_deleted' => 'Έσβησε προσφορά',
        'quote_renewed' => 'Ανανέωσε προσφορά',
        'reminder_sent' => 'Στάλθηκε υπενθύμιση',
        'reminders_off' => 'Σταμάτησε τις υπενθυμίσεις',
        'wizard_used' => 'Είδε προτάσεις στον οδηγό',
        'staff_created' => 'Νέος πελάτης από τη διαχείριση',
        'invited' => 'Στάλθηκε πρόσκληση λογαριασμού',
        'activated' => 'Ενεργοποίησε τον λογαριασμό',
        'staff_quote' => 'Προσφορά από το προσωπικό',
        'quote_sent' => 'Στάλθηκε πρόταση στον πελάτη',
        'quote_edited' => 'Αλλαγές από το προσωπικό',
        'quote_changed' => 'Άλλαξε την προσφορά του',
        'coupon_issued' => 'Πήρε κουπόνι καλωσορίσματος',
        'marketing_on' => 'Δέχεται προσφορές στο email',
        'marketing_off' => 'Σταμάτησε τις προσφορές στο email',
        'coupon_applied' => 'Χρησιμοποίησε κουπόνι',
    ];

    protected $fillable = ['user_id', 'quote_id', 'type', 'summary', 'meta'];

    protected function casts(): array
    {
        return ['meta' => 'array', 'created_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class)->withTrashed();
    }
}
