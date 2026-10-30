<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Το «Studio μου» είναι για πελάτες με στοιχεία επιχείρησης· το προσωπικό πάει στη διαχείριση */
class EnsureCustomer
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->isCustomer()) {
            return redirect('/admin');
        }

        abort_if($user && ! $user->business, 403, 'Ο λογαριασμός δεν έχει στοιχεία επιχείρησης.');

        return $next($request);
    }
}
