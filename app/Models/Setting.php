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
        'company_name' => 'On Air Media Group',
        'phone' => null,
        'email' => null,
        'address' => null,
        'vat' => null,
        'gemi' => null,
        'socials' => [],
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
