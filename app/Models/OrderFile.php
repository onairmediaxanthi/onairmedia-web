<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Υλικό καμπάνιας: αρχεία που ανεβάζει ο πελάτης (ή η ομάδα, π.χ. το τελικό σποτ για έγκριση) */
class OrderFile extends Model
{
    protected $fillable = ['path', 'name', 'size', 'from', 'user_id'];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sizeLabel(): string
    {
        $b = (int) $this->size;

        return $b >= 1048576 ? number_format($b / 1048576, 1, ',', '.').' MB' : max(1, (int) round($b / 1024)).' KB';
    }

    public function extension(): string
    {
        return strtolower(pathinfo($this->name, PATHINFO_EXTENSION));
    }
}
