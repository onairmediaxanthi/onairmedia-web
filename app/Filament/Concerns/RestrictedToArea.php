<?php

namespace App\Filament\Concerns;

use Filament\Facades\Filament;
use Illuminate\Auth\Access\Response;
use Illuminate\Database\Eloquent\Model;

/**
 * Περιορίζει ένα resource σε μια «περιοχή» (π.χ. sales). Ισχύει για όλες τις ενέργειες:
 * λίστα, προβολή, δημιουργία, αλλαγή, διαγραφή, ακόμα κι αν κάποιος ανοίξει το URL απευθείας.
 * Κάθε resource ορίζει: protected static string $area = '...';
 */
trait RestrictedToArea
{
    public static function getAuthorizationResponse(string $action, ?Model $record = null): Response
    {
        if (! Filament::auth()->user()?->canAccessArea(static::$area)) {
            return Response::deny('Δεν έχετε πρόσβαση σε αυτή την περιοχή.');
        }

        return parent::getAuthorizationResponse($action, $record);
    }
}
