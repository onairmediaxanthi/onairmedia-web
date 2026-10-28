<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateBusinessRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AccountController extends Controller
{
    public function show(Request $request): View
    {
        return view('account.show', [
            'user' => $request->user(),
            'business' => $request->user()->business,
            'quotes' => $request->user()->quotes()->latest()->limit(5)->get(),
            'quoteCount' => $request->user()->quotes()->count(),
            'coupons' => \App\Models\Coupon::query()->where('user_id', $request->user()->id)->where('is_active', true)
                ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>', now()))
                ->whereDoesntHave('redemptions')->latest()->get(),
        ]);
    }

    public function edit(Request $request): View
    {
        return view('account.edit', ['business' => $request->user()->business]);
    }

    public function update(UpdateBusinessRequest $request): RedirectResponse
    {
        $business = $request->user()->business;
        $vatChanged = $business->vat !== $request->validated('vat');
        $business->fill($request->validated());
        if ($vatChanged) {
            $business->vat_verified_at = null;
        }
        $business->save();

        return redirect()->route('account')->with('status', 'Τα στοιχεία της επιχείρησης ενημερώθηκαν.');
    }

    public function marketing(Request $request): RedirectResponse
    {
        $on = $request->boolean('marketing');
        $request->user()->business->setMarketingConsent($on, 'account');

        return back()->with('status', $on
            ? 'Θα λαμβάνετε τις προσφορές μας στο email σας.'
            : 'Δεν θα λαμβάνετε πλέον προσφορές στο email σας.');
    }

    /** Δικαίωμα διαγραφής (GDPR άρθρο 17) */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate(['password' => ['required', 'string']]);

        if (! Hash::check($request->input('password'), $request->user()->password)) {
            throw ValidationException::withMessages(['password' => 'Ο κωδικός δεν είναι σωστός.'])->errorBag('delete');
        }

        $user = $request->user();
        Auth::logout();
        $user->delete(); // η επιχείρηση διαγράφεται μαζί (cascade)
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}

