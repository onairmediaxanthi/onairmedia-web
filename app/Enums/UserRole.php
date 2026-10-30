<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case Sales = 'sales';
    case Studio = 'studio';
    case Accounting = 'accounting';
    case Customer = 'customer';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Διαχειριστής',
            self::Sales => 'Πωλήσεις',
            self::Studio => 'Studio παραγωγής',
            self::Accounting => 'Λογιστήριο',
            self::Customer => 'Πελάτης',
        };
    }

    /** Περιοχές της διαχείρισης που βλέπει κάθε ρόλος */
    public const AREAS = [
        'site' => 'Αρχική σελίδα & ρυθμίσεις site',
        'media' => 'Σταθμοί',
        'sales' => 'Πωλήσεις (πελάτες, προσφορές, πακέτα, κουπόνια)',
        'pricing' => 'Τιμοκατάλογος',
        'settings' => 'Ρυθμίσεις προσφορών & ειδοποιήσεων',
        'users' => 'Χρήστες διαχείρισης',
    ];

    /** @return list<string> */
    public function areas(): array
    {
        return match ($this) {
            self::Admin => array_keys(self::AREAS),
            self::Sales => ['sales'],
            self::Studio => ['media', 'site'],
            self::Accounting => ['sales', 'pricing'],
            self::Customer => [],
        };
    }

    /** @return array<string, string> ρόλοι προσωπικού για επιλογή */
    public static function staffOptions(): array
    {
        return collect(self::cases())->filter(fn (self $r) => $r->isStaff())->mapWithKeys(fn (self $r) => [$r->value => $r->label()])->all();
    }

    public function isStaff(): bool
    {
        return $this !== self::Customer;
    }
}
