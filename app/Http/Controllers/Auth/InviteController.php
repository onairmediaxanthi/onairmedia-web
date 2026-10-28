<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\Quote;
use App\Models\User;
use App\Services\CustomerEvents;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/** Ενεργοποίηση λογαριασμού πελάτη που άνοιξε το προσωπικό */
class InviteController extends Controller
{
    protected function check(User $user): ?RedirectResponse
    {
        abort_unless($user->isCustomer() && $user->business, 404);

        return $user->hasVerifiedEmail()
            ? redirect()->route('login')->with('status', 'Ο λογαριασμός σας είναι ήδη ενεργός. Συνδεθείτε με το email και τον κωδικό σας.')
            : null;
    }

    public function show(Request $request, User $user): View|RedirectResponse
    {
        return $this->check($user) ?? view('auth.invite', ['user' => $user, 'business' => $user->business]);
    }

    public function store(Request $request, User $user): RedirectResponse
    {
        if ($r = $this->check($user)) {
            return $r;
        }
        $data = $request->validate([
            'password' => ['required', 'confirmed', Password::min(10)->letters()->mixedCase()->numbers()->uncompromised()],
            'terms' => ['accepted'],
            'marketing' => ['nullable', 'boolean'],
        ], [
            'terms.accepted' => 'Χρειάζεται να αποδεχτείτε τους όρους.',
            'password.uncompromised' => 'Αυτός ο κωδικός έχει εμφανιστεί σε διαρροές δεδομένων. Διαλέξτε άλλον.',
        ]);

        $user->password = $data['password'];
        $user->email_verified_at = now(); // το link ήρθε στο email του
        $user->save();

        $b = $user->business;
        $b->terms_accepted_at = now();
        $b->terms_version = Business::TERMS_VERSION;
        $b->marketing_consent_at = ! empty($data['marketing']) ? now() : null;
        $b->save();

        Auth::guard('web')->login($user);
        $request->session()->regenerate();
        CustomerEvents::record('activated', $user);

        $quote = $request->query('q') ? Quote::query()->where('ulid', $request->query('q'))->where('user_id', $user->id)->first() : null;

        return redirect($quote ? route('quotes.show', $quote) : route('account'))
            ->with('status', 'Ο λογαριασμός σας ενεργοποιήθηκε. Καλώς ήρθατε στο Studio σας!');
    }
}
