<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/** Θέση banner στο thrakitoday.gr, με τιμή ανά ημέρα (χωρίς ΦΠΑ) */
class AdSlot extends Model
{
    public const DEVICES = ['all' => 'Όλες οι συσκευές', 'desktop' => 'Μόνο υπολογιστής', 'mobile' => 'Μόνο κινητό'];

    protected $fillable = [
        'code', 'name', 'description', 'dimensions', 'device', 'price_per_day',
        'min_days', 'max_concurrent', 'preview_path', 'is_active', 'sort',
    ];

    protected function casts(): array
    {
        return ['price_per_day' => 'decimal:2', 'is_active' => 'boolean'];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderBy('sort');
    }
}
