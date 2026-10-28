<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Slide extends Model
{
    public const EFFECTS = [
        'zoom' => 'Αργό ζουμ (Ken Burns)',
        'pan' => 'Οριζόντια κίνηση',
        'fade' => 'Απλό σβήσιμο',
    ];

    protected $fillable = [
        'title', 'subtitle', 'image_path', 'image_mobile_path', 'cta_label', 'cta_url',
        'effect', 'text_position', 'starts_at', 'ends_at', 'sort', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    /** Ενεργά slides που είναι μέσα στο χρονικό τους παράθυρο */
    #[Scope]
    protected function showing(Builder $query): void
    {
        $query->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>', now()))
            ->orderBy('sort');
    }

    protected function imageUrl(): Attribute
    {
        return Attribute::get(fn () => Storage::disk('public')->url($this->image_path));
    }

    protected function imageMobileUrl(): Attribute
    {
        return Attribute::get(fn () => $this->image_mobile_path ? Storage::disk('public')->url($this->image_mobile_path) : null);
    }

    /** Μόνο σχετικά links ή https — ποτέ javascript: κ.λπ. */
    protected function safeCtaUrl(): Attribute
    {
        return Attribute::get(function () {
            $url = trim((string) $this->cta_url);

            return ($url !== '' && (str_starts_with($url, '/') && ! str_starts_with($url, '//') || str_starts_with($url, 'https://'))) ? $url : null;
        });
    }
}
