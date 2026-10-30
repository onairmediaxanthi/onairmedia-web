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
        'quote_cta' => 'Κουμπί «Βγάλτε προσφορά» / εγγραφή',
        'dial' => 'Δείκτης FM (Στον αέρα τώρα)',
        'stations' => 'Κάρτες σταθμών',
        'packages' => 'Πακέτα & προσφορές',
        'offers' => 'Πάνελ προσφορών (slider)',
        'news' => 'Ειδήσεις thrakitoday.gr',
        'media' => 'Τα μέσα μας (ραδιόφωνο, εφημερίδα, οθόνη)',
        'banner' => 'Banner με εικόνα και κουμπί',
        'app' => 'Η εφαρμογή μας (App Store / Google Play)',
        'stats' => 'Αριθμοί που εντυπωσιάζουν',
        'steps' => 'Πώς λειτουργεί (βήματα)',
        'faq' => 'Συχνές ερωτήσεις',
        'logos' => 'Λογότυπα πελατών / συνεργατών',
        'video' => 'Βίντεο (YouTube / Vimeo)',
        'contact' => 'Επικοινωνία',
        'text' => 'Ελεύθερο κείμενο',
    ];

    /** Τύποι που δεν έχουν νόημα δύο φορές στην ίδια σελίδα */
    public const SINGLETONS = ['slider', 'dial', 'stations', 'news', 'app', 'contact'];

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

    public const BACKGROUNDS = [
        'none' => 'Χωρίς (όπως η σελίδα)',
        'surface' => 'Λευκό / κάρτα',
        'paper' => 'Ανοιχτό γκρι',
        'night' => 'Σκούρο μπλε',
        'onair' => 'Ματζέντα On Air',
        'gradient' => 'Διαβάθμιση ματζέντα → μπλε',
        'glow' => 'Κίτρινο',
        'custom' => 'Δικό μου χρώμα',
        'image' => 'Εικόνα φόντου',
    ];

    /** Χαρακτηριστικά του «περιτυλίγματος» της ενότητας: φόντο, αποστάσεις, πλάτος, στοίχιση, κίνηση */
    public function wrapperAttributes(): array
    {
        $st = (array) $this->setting('style', []);
        $bg = $st['bg'] ?? 'none';
        $hex = fn ($v, $d) => is_string($v) && preg_match('/^#[0-9a-fA-F]{6}$/', $v) ? $v : $d;
        $style = match ($bg) {
            'surface' => 'background: var(--color-surface)',
            'paper' => 'background: var(--color-paper)',
            'night' => 'background: #121a33',
            'onair' => 'background: #c80c92',
            'gradient' => 'background: linear-gradient(135deg, #c80c92 0%, #121a33 75%)',
            'glow' => 'background: #ffb627',
            'custom' => 'background: '.$hex($st['color'] ?? null, '#121a33'),
            default => '',
        };
        if ($bg === 'image' && ! empty($st['image'])) {
            $o = max(0, min(90, (int) ($st['overlay'] ?? 55))) / 100;
            $url = \Illuminate\Support\Facades\Storage::disk('public')->url($st['image']);
            $style = "background: linear-gradient(rgb(18 26 51 / {$o}), rgb(18 26 51 / {$o})), url('".e($url)."') center/cover no-repeat";
        }
        $darkBg = in_array($bg, ['night', 'onair', 'gradient', 'image'], true)
            || ($bg === 'custom' && self::isDark($hex($st['color'] ?? null, '#121a33')));
        $tone = $st['tone'] ?? 'auto';
        $toneClass = match (true) {
            $tone === 'light', $tone === 'auto' && $darkBg => 'sec-tone-light',
            $tone === 'dark', $tone === 'auto' && $bg === 'glow' => 'sec-tone-dark',
            default => '',
        };

        return [
            'class' => trim($toneClass.' '.($bg !== 'none' ? 'relative' : '')),
            'style' => $style,
            'data-pad' => $st['pad'] ?? 'normal',
            'data-width' => $st['width'] ?? 'normal',
            'data-align' => $st['align'] ?? 'auto',
            'data-title' => $st['title_size'] ?? 'md',
            'animate' => ($st['animate'] ?? true) !== false,
        ];
    }

    protected static function isDark(string $hex): bool
    {
        [$r, $g, $b] = sscanf($hex, '#%02x%02x%02x');

        return (0.299 * $r + 0.587 * $g + 0.114 * $b) < 150;
    }

    public function label(): string
    {
        return $this->admin_label ?: ($this->title ?: (self::TYPES[$this->type] ?? $this->type));
    }
}
