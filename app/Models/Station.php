<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Station extends Model
{
    protected $fillable = [
        'slug', 'name', 'frequency', 'slogan', 'description', 'genre', 'coverage',
        'stream_url', 'now_playing_mount', 'logo_path', 'cover_path', 'color',
        'website', 'socials', 'seo_title', 'seo_description', 'sort', 'is_active',
        'audience', 'spot_seconds', 'price_spot', 'price_mention_live', 'price_mention_recorded',
        'metro_price_second', 'metro_mention_live', 'metro_mention_recorded', 'pct_first_position', 'pct_middle_spot', 'pct_sandwich', 'pct_fixed_hour', 'max_spots_per_hour', 'is_bookable',
        'airplay_enabled', 'chart_public',
    ];

    protected function casts(): array
    {
        return [
            'frequency' => 'decimal:1',
            'socials' => 'array',
            'airplay_enabled' => 'boolean',
            'chart_public' => 'boolean',
            'last_airplay_at' => 'datetime',
            'last_seen_at' => 'datetime',
            'audience' => 'array',
            'price_spot' => 'decimal:2',
            'price_mention_live' => 'decimal:2',
            'price_mention_recorded' => 'decimal:2',
            'metro_price_second' => 'decimal:4',
            'metro_mention_live' => 'decimal:2',
            'metro_mention_recorded' => 'decimal:2',
            'pct_first_position' => 'decimal:2',
            'pct_middle_spot' => 'decimal:2',
            'pct_sandwich' => 'decimal:2',
            'pct_fixed_hour' => 'decimal:2',
            'is_bookable' => 'boolean',
            'is_active' => 'boolean',
            'sort' => 'integer',
        ];
    }

    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('is_active', true)->orderBy('sort')->orderBy('name');
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    protected function logoUrl(): Attribute
    {
        return Attribute::get(fn () => $this->logo_path ? Storage::disk('public')->url($this->logo_path) : null);
    }

    protected function coverUrl(): Attribute
    {
        return Attribute::get(fn () => $this->cover_path ? Storage::disk('public')->url($this->cover_path) : null);
    }

    /** Π.χ. «88.8 FM» */
    protected function frequencyLabel(): Attribute
    {
        return Attribute::get(fn () => $this->frequency ? preg_replace('/\.0$/', '', number_format((float) $this->frequency, 1, '.', '')).' FM' : null);
    }
}
