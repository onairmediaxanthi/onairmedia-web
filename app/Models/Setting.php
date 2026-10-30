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
        'callback_emails' => null,
        // Παραγγελίες & πληρωμές
        'bank_accounts' => [],
        'payment_due_days' => 3,
        'payment_office_enabled' => true,
        'notify_deposit_declared' => true,
        'notify_order_message' => true,
        'notify_files_uploaded' => true,
        'notify_materials_done' => true,
        'callback_floating' => true,
        'max_staff_discount' => 50,
        'pdf_design' => 'classic',
        // Τι παίζουν οι σταθμοί & Top 40
        'airplay_enabled' => true,
        'airplay_keep_days' => 30,
        'chart_size' => 40,
        'chart_min_plays' => 2,
        'airplay_require_artist' => true,
        'airplay_ignore' => ['live24', 'on air', 'onair', 'jingle', 'διαφημιση', 'διαφήμιση', 'spot', 'ειδησεις', 'ειδήσεις', 'δελτιο', 'δελτίο', 'advertisement', 'commercial'],
        // Υλικό καμπάνιας: όρια ελέγχου αρχείων
        'spot_tolerance_seconds' => 3,
        'spot_min_kbps' => 128,
        'spot_good_kbps' => 256,
        'banner_max_kb' => 300,
        'metro_cities' => ['Αθήνα', 'Θεσσαλονίκη', 'Πάτρα'],
        'metro_price_sponsored_article' => null,
        'metro_price_article_writing' => null,
        'metro_price_spot_production' => null,
        'metro_price_spot_production_voices' => null,
        'metro_price_led_graphic' => null,
        'metro_price_banner_design' => null,
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
        // Στην cache μόνο ό,τι έχει αποθηκευτεί· οι προεπιλογές μπαίνουν κάθε φορά, ώστε μια νέα ρύθμιση
        // (μετά από ενημέρωση του site) να ισχύει αμέσως, χωρίς να χρειάζεται καθάρισμα της cache.
        return array_merge(self::DEFAULTS, Cache::rememberForever('settings:stored', fn () => static::query()->pluck('value', 'key')->all()));
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
        Cache::forget('settings:stored');
    }

    public static function url(string $key): ?string
    {
        $path = static::get($key);

        return $path ? Storage::disk('public')->url($path) : null;
    }
}
