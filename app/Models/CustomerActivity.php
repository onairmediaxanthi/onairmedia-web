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
