<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmailVerificationController extends Controller
{
    public function notice(Request $request): View|RedirectResponse
    {
        return $request->user()->hasVerifiedEmail()
            ? redirect()->route('account')
            : view('auth.verify-email');
    }

    public function verify(EmailVerificationRequest $request): RedirectResponse
    {
        $request->fulfill();
        \App\Services\CustomerEvents::record('verified', $request->user());
        app(\App\Services\Coupons::class)->issueWelcome($request->user());

        return redirect()->intended(route('account'))->with('status', 'Το email σας επιβεβαιώθηκε. Τώρα βλέπετε τιμές και μπορείτε να κλείσετε καμπάνια.');
    }

    public function send(Request $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->route('account');
        }

        $request->user()->sendEmailVerificationNotification();

        return back()->with('status', 'Στείλαμε νέο σύνδεσμο επιβεβαίωσης στο '.$request->user()->email.'.');
    }
}
