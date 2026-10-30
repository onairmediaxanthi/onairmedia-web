<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * Κουπόνι έκπτωσης. Γενικό (κωδικός καμπάνιας για όλους) ή προσωπικό (π.χ. δώρο εγγραφής).
 * Η έκπτωση αφαιρείται πριν τον ΦΠΑ (άρθρο 19 §5α Κώδικα ΦΠΑ).
 */
class Coupon extends Model
{
    public const TYPES = ['percent' => 'Ποσοστό %', 'fixed' => 'Σταθερό ποσό €'];

    public const SOURCES = ['campaign' => 'Καμπάνια', 'welcome' => 'Δώρο εγγραφής', 'personal' => 'Προσωπικό'];

    protected $fillable = [
        'code', 'name', 'description', 'type', 'value', 'min_subtotal', 'max_discount', 'starts_at', 'ends_at',
        'usage_limit', 'per_customer_limit', 'user_id', 'source', 'applies_to_packages', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'decimal:2', 'min_subtotal' => 'decimal:2', 'max_discount' => 'decimal:2',
            'starts_at' => 'datetime', 'ends_at' => 'datetime', 'applies_to_packages' => 'boolean', 'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(fn (Coupon $c) => $c->code = self::normalize($c->code));
    }

    public static function normalize(?string $code): string
    {
        return Str::upper(preg_replace('/[^A-Za-z0-9-]/', '', (string) $code));
    }

    /** Κωδικός χωρίς μπερδεμένους χαρακτήρες (0/O, 1/I) */
    public static function generateCode(string $prefix = ''): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        do {
            $code = ($prefix ? $prefix.'-' : '').collect(range(1, 6))->map(fn () => $alphabet[random_int(0, strlen($alphabet) - 1)])->implode('');
        } while (static::query()->where('code', $code)->exists());

        return $code;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function redemptions(): HasMany
    {
        return $this->hasMany(CouponRedemption::class);
    }

    public function valueLabel(): string
    {
        return $this->type === 'percent'
            ? rtrim(rtrim(number_format((float) $this->value, 2, ',', ''), '0'), ',').'%'
            : number_format((float) $this->value, 0, ',', '.').' €';
    }

    public function isUsable(): bool
    {
        return $this->is_active && (! $this->starts_at || $this->starts_at->isPast()) && (! $this->ends_at || $this->ends_at->isFuture())
            && (! $this->usage_limit || $this->redemptions()->count() < $this->usage_limit);
    }
}
