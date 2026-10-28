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
    ];

    protected function casts(): array
    {
        return [
            'frequency' => 'decimal:1',
            'socials' => 'array',
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
