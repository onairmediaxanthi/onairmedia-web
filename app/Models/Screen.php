<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/** Ψηφιακή πινακίδα LED, με τιμή προβολής ανά ημέρα (χωρίς ΦΠΑ) */
class Screen extends Model
{
    protected $fillable = [
        'slug', 'name', 'location', 'map_url', 'description', 'dimensions', 'resolution',
        'slot_seconds', 'loops_per_hour', 'operating_from', 'operating_to', 'price_per_day',
        'min_days', 'max_advertisers', 'photo_path', 'is_active', 'sort',
    ];

    protected function casts(): array
    {
        return ['price_per_day' => 'decimal:2', 'is_active' => 'boolean'];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderBy('sort');
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
