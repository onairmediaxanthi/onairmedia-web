<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Το site είναι μόνο για πελάτες: λογαριασμός προσωπικού δεν μένει ποτέ συνδεδεμένος εδώ
 * (π.χ. παλιές συνεδρίες). Η διαχείριση έχει δική της, ξεχωριστή σύνδεση.
 */
class KeepStaffOffSite
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::guard('web')->user()?->role?->isStaff()) {
            Auth::guard('web')->logout();
        }

        return $next($request);
    }
}
