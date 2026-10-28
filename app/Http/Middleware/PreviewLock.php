<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Όσο το PREVIEW_PASSWORD είναι συμπληρωμένο, το site ζητά κωδικό.
 * Οι ειδοποιήσεις πληρωμών (/webhooks/*) και το /up περνούν πάντα.
 */
class PreviewLock
{
    public const SESSION_KEY = 'preview_unlocked';

    public function handle(Request $request, Closure $next): Response
    {
        $password = config('app.preview_password');

        if (blank($password)
            || $request->session()->get(self::SESSION_KEY) === true
            || $request->is('preview-unlock', 'up', 'webhooks/*', 'aposyndromi/*')) {
            return $next($request);
        }

        return response()
            ->view('preview-lock', status: 401)
            ->header('X-Robots-Tag', 'noindex, nofollow');
    }
}
