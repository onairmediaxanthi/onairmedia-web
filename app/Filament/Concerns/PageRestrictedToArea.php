<?php

namespace App\Filament\Concerns;

use Filament\Facades\Filament;

/** Το ίδιο για σελίδες (Pages): protected static string $area = '...'; */
trait PageRestrictedToArea
{
    public static function canAccess(): bool
    {
        return (bool) Filament::auth()->user()?->canAccessArea(static::$area);
    }
}
