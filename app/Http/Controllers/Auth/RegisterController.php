<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterRequest;
use App\Models\Business;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class RegisterController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    public function store(RegisterRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $user = DB::transaction(function () use ($data, $request) {
            $user = User::create([
                'name' => $data['contact_person'],
                'email' => $data['email'],
                'password' => $data['password'],
            ]);

            $business = new Business(collect($data)->only((new Business)->getFillable())->all());
            $business->terms_accepted_at = now();
            $business->terms_version = Business::TERMS_VERSION;
            $business->marketing_consent_at = ! empty($data['marketing']) ? now() : null;
            $business->marketing_consent_source = ! empty($data['marketing']) ? 'register' : null;
            $business->signup_ip = $request->ip();
            $user->business()->save($business);

            return $user;
        });

        event(new Registered($user)); // στέλνει το email επαλήθευσης
        Auth::login($user);
        \App\Services\CustomerEvents::record('registered', $user->load('business'));
        $request->session()->regenerate();

        return redirect()->route('verification.notice');
    }
}
