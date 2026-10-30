<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(\App\Http\Middleware\SecurityHeaders::class);
        // Διαγραφή με ένα κλικ (RFC 8058): οι πάροχοι email στέλνουν POST χωρίς CSRF token
        $middleware->validateCsrfTokens(except: ['aposyndromi/*']);
        $middleware->web(append: [\App\Http\Middleware\PreviewLock::class, \App\Http\Middleware\KeepStaffOffSite::class]);
        // Ο server του cPanel είναι πίσω από proxy (LiteSpeed/Cloudflare)
        $middleware->trustProxies(at: '*');
        $middleware->redirectUsersTo(fn () => route('account'));
        $middleware->redirectGuestsTo(fn () => route('login'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Πολύ μεγάλο ανέβασμα (πάνω από το post_max_size του server): πίσω στη σελίδα με εξήγηση, όχι σελίδα σφάλματος
        $exceptions->render(function (\Illuminate\Http\Exceptions\PostTooLargeException $e, \Illuminate\Http\Request $request) {
            if ($request->expectsJson()) {
                return null;
            }

            // Το session δεν έχει ξεκινήσει ακόμα σε αυτό το σημείο: αυτόνομη σελίδα με επιστροφή
            return response()->view('errors.upload-too-large', ['back' => $request->headers->get('referer') ?: url('/to-studio-mou/paraggelies')], 413);
        });
    })->create();
