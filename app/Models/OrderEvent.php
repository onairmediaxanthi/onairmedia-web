<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Ιστορικό παραγγελίας: αλλαγές σταδίου και σημειώσεις (εσωτερικές ή προς τον πελάτη) */
class OrderEvent extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['stage', 'title', 'note', 'internal', 'notified', 'user_id'];

    protected function casts(): array
    {
        return ['internal' => 'boolean', 'notified' => 'boolean'];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
