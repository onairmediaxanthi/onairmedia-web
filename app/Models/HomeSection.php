<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Μία «ενότητα» της αρχικής. Η σειρά, η ορατότητα ανά συσκευή και
 * οι ρυθμίσεις κάθε τύπου ορίζονται από τη διαχείριση.
 */
class HomeSection extends Model
{
    public const TYPES = [
        'slider' => 'Slider προσφορών',
        'dial' => 'Δείκτης FM (Στον αέρα τώρα)',
        'stations' => 'Κάρτες σταθμών',
        'packages' => 'Πακέτα & προσφορές',
        'news' => 'Ειδήσεις thrakitoday.gr',
        'media' => 'Τα μέσα μας (ραδιόφωνο, εφημερίδα, οθόνη)',
        'banner' => 'Banner με εικόνα και κουμπί',
        'text' => 'Ελεύθερο κείμενο',
    ];

    /** Τύποι που δεν έχουν νόημα δύο φορές στην ίδια σελίδα */
    public const SINGLETONS = ['slider', 'dial', 'stations', 'news'];

    protected $fillable = [
        'type', 'admin_label', 'title', 'subtitle', 'anchor', 'settings',
        'show_mobile', 'show_tablet', 'show_desktop', 'starts_at', 'ends_at', 'sort', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'settings' => 'array',
            'show_mobile' => 'boolean',
            'show_tablet' => 'boolean',
            'show_desktop' => 'boolean',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    #[Scope]
    protected function showing(Builder $query): void
    {
        $query->where('is_active', true)
            ->where(fn ($q) => $q->where('show_mobile', true)->orWhere('show_tablet', true)->orWhere('show_desktop', true))
            ->where(fn ($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>', now()))
            ->orderBy('sort');
    }

    public function setting(string $key, mixed $default = null): mixed
    {
        return data_get($this->settings, $key, $default);
    }

    /**
     * Κλάσεις που κρύβουν την ενότητα ανά μέγεθος οθόνης.
     * Κινητό < 640px · Tablet 640–1023px · Υπολογιστής ≥ 1024px
     */
    public function visibilityClasses(): string
    {
        return implode(' ', array_filter([
            $this->show_mobile ? null : 'max-sm:hidden',
            $this->show_tablet ? null : 'sm:max-lg:hidden',
            $this->show_desktop ? null : 'lg:hidden',
        ]));
    }

    public function label(): string
    {
        return $this->admin_label ?: ($this->title ?: (self::TYPES[$this->type] ?? $this->type));
    }
}
