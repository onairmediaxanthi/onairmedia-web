<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

/**
 * Ρυθμίσεις site (λογότυπα, στοιχεία επικοινωνίας κ.λπ.) ως ζεύγη κλειδί/τιμή.
 * Διαβάζονται όλες μαζί από cache· κάθε αποθήκευση την καθαρίζει.
 */
class Setting extends Model
{
    protected $primaryKey = 'key';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['key', 'value'];

    protected function casts(): array
    {
        return ['value' => 'json'];
    }

    public const DEFAULTS = [
        'logo_light' => 'brand/onairmedia-letters.webp',
        'logo_dark' => null,
        'favicon' => null,
        'logo_thrakitoday' => null,
        'logo_led' => null,
        'company_name' => 'On Air Media Group',
        'phone' => null,
        'email' => null,
        'address' => null,
        'vat' => null,
        'gemi' => null,
        'socials' => [],
        // Τιμοκατάλογος: λοιπές χρεώσεις (χωρίς ΦΠΑ)
        'vat_rate' => 24,
        'price_sponsored_article' => null,
        'price_article_writing' => null,
        'price_spot_production' => null,
        'price_spot_production_voices' => null,
        'price_led_graphic' => null,
        'price_banner_design' => null,
        // Προσφορές & ειδοποιήσεις
        'quote_valid_days' => 15,
        'quote_reminder_days' => 3,
        'quote_reminder_max' => 3,
        'notify_emails' => null,
        'notify_registered' => true,
        'notify_quote_saved' => true,
        'notify_quote_changed' => true,
        'notify_quote_submitted' => true,
        'notify_quote_deleted' => true,
        'notify_quote_pdf' => false,
        'notify_wizard_used' => false,
        'campaign_per_minute' => 20,
        // Κουπόνι καλωσορίσματος
        'welcome_coupon_enabled' => false,
        'welcome_coupon_type' => 'percent',
        'welcome_coupon_value' => 10,
        'welcome_coupon_days' => 30,
        'welcome_coupon_min' => null,
        'welcome_coupon_prefix' => 'KALOS',
        'footer_text' => 'Δώδεκα ραδιόφωνα, το thrakitoday.gr και η Οθόνη Πόλης. Η φωνή της Ξάνθης, από το 88.8 ως το 106.',
    ];

    /** @return array<string, mixed> */
    public static function allValues(): array
    {
        return Cache::rememberForever('settings:all', fn () => array_merge(
            self::DEFAULTS,
            static::query()->pluck('value', 'key')->all(),
        ));
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return static::allValues()[$key] ?? $default;
    }

    /** @param array<string, mixed> $values */
    public static function putMany(array $values): void
    {
        foreach ($values as $key => $value) {
            static::query()->updateOrCreate(['key' => $key], ['value' => $value]);
        }
        Cache::forget('settings:all');
    }

    public static function url(string $key): ?string
    {
        $path = static::get($key);

        return $path ? Storage::disk('public')->url($path) : null;
    }
}
