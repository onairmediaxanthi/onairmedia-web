<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/**
 * Έτοιμο πακέτο προβολής ή προσφορά (με λήξη).
 * Η τιμή φαίνεται μόνο σε συνδεδεμένους, επαληθευμένους επαγγελματίες.
 */
class Package extends Model
{
    public const MEDIA = [
        'radio' => 'Ραδιόφωνο',
        'news' => 'thrakitoday.gr',
        'led' => 'Οθόνη Πόλης',
        'creative' => 'Δημιουργικό',
    ];

    protected $fillable = [
        'slug', 'name', 'tagline', 'description', 'includes', 'media_mix', 'duration_days',
        'price', 'pricing', 'price_tier', 'discount_percent', 'specs', 'compare_at_price', 'badge', 'color', 'image_path', 'is_offer',
        'available_from', 'available_until', 'is_featured', 'sort', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'includes' => 'array',
            'specs' => 'array',
            'discount_percent' => 'decimal:2',
            'price' => 'decimal:2',
            'compare_at_price' => 'decimal:2',
            'is_offer' => 'boolean',
            'is_featured' => 'boolean',
            'is_active' => 'boolean',
            'available_from' => 'datetime',
            'available_until' => 'datetime',
        ];
    }

    #[Scope]
    protected function available(Builder $query): void
    {
        $query->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('available_from')->orWhere('available_from', '<=', now()))
            ->where(fn ($q) => $q->whereNull('available_until')->orWhere('available_until', '>', now()))
            ->orderBy('sort');
    }

    public const TIERS = ['all' => 'Για όλους', 'local' => 'Μόνο βασικός τιμοκατάλογος', 'metro' => 'Μόνο μεγάλα αστικά κέντρα'];

    public function availableFor(string $tier): bool
    {
        return in_array($this->price_tier ?: 'all', ['all', $tier], true);
    }

    /** Πακέτα που βλέπει ένας πελάτης ανάλογα με τον τιμοκατάλογό του */
    public function scopeForTier(Builder $query, string $tier): Builder
    {
        return $query->whereIn('price_tier', ['all', $tier]);
    }

    /** Πόσες ημέρες μένουν μέχρι τη λήξη της προσφοράς */
    public function daysLeft(): ?int
    {
        return $this->available_until ? max(0, (int) ceil(now()->diffInHours($this->available_until, false) / 24)) : null;
    }

    /** Οι σταθμοί του πακέτου (για λογότυπα στα πάνελ) */
    public function stations(): \Illuminate\Support\Collection
    {
        $ids = collect((array) $this->specs)->where('type', 'radio')->pluck('station_id')->filter()->all();

        return $ids ? Station::query()->whereIn('id', $ids)->orderBy('sort')->get() : collect();
    }

    /** @return list<string> */
    public function mediaList(): array
    {
        return array_values(array_filter(explode(',', (string) $this->media_mix)));
    }

    protected function imageUrl(): Attribute
    {
        return Attribute::get(fn () => $this->image_path ? Storage::disk('public')->url($this->image_path) : null);
    }

    /** Ποσοστό έκπτωσης όταν υπάρχει «παλιά» τιμή */
    protected function discountPercent(): Attribute
    {
        return Attribute::get(function () {
            if (! $this->price || ! $this->compare_at_price || $this->compare_at_price <= $this->price) {
                return null;
            }

            return (int) round((1 - $this->price / $this->compare_at_price) * 100);
        });
    }

    public static function formatPrice(float|string|null $amount): ?string
    {
        return $amount === null ? null : number_format((float) $amount, 0, ',', '.').' €';
    }
}
