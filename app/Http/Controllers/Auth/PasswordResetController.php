<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\View\View;

class PasswordResetController extends Controller
{
    public function request(): View
    {
        return view('auth.forgot-password');
    }

    public function email(Request $request): RedirectResponse
    {
        $request->validate(['email' => ['required', 'email']]);
        \App\Support\Turnstile::check($request);

        Password::sendResetLink($request->only('email'));

        // Ίδια απάντηση πάντα: δεν αποκαλύπτουμε ποια email έχουν λογαριασμό
        return back()->with('status', 'Αν υπάρχει λογαριασμός με αυτό το email, θα λάβετε σύνδεσμο για νέο κωδικό σε λίγα λεπτά.');
    }

    public function edit(Request $request, string $token): View
    {
        return view('auth.reset-password', ['token' => $token, 'email' => $request->query('email')]);
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::min(10)->letters()->mixedCase()->numbers()->uncompromised()],
        ], [
            'password.uncompromised' => 'Αυτός ο κωδικός έχει εμφανιστεί σε διαρροές δεδομένων. Διαλέξτε άλλον.',
        ], ['password' => 'κωδικός']);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill(['password' => $password, 'remember_token' => Str::random(60)])->save();
                event(new PasswordReset($user));
            }
        );

        return $status === Password::PASSWORD_RESET
            ? redirect()->route('login')->with('status', 'Ο κωδικός άλλαξε. Συνδεθείτε με τον νέο.')
            : back()->withInput($request->only('email'))->withErrors(['email' => 'Ο σύνδεσμος έληξε ή δεν είναι έγκυρος. Ζητήστε νέο.']);
    }
}
