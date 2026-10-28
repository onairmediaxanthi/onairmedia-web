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

    public function isStaff(): bool
    {
        return $this !== self::Customer;
    }
}
