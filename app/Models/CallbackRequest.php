<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CallbackRequest extends Model
{
    public const STATUSES = ['new' => 'Νέο', 'contacted' => 'Επικοινωνήσαμε', 'done' => 'Ολοκληρώθηκε'];

    protected $fillable = ['first_name', 'last_name', 'mobile', 'vat', 'business_id', 'user_id', 'page', 'status', 'notes'];

    protected function casts(): array
    {
        return ['handled_at' => 'datetime'];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function handledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by_id');
    }

    public function fullName(): string
    {
        return trim($this->first_name.' '.$this->last_name);
    }
}
