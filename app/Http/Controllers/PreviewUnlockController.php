<?php

namespace App\Http\Controllers;

use App\Http\Middleware\PreviewLock;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PreviewUnlockController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $request->validate(['password' => ['required', 'string', 'max:200']]);

        $expected = (string) config('app.preview_password');

        if ($expected === '' || ! hash_equals($expected, (string) $request->input('password'))) {
            return back()->withErrors(['password' => 'Λάθος κωδικός.']);
        }

        $request->session()->regenerate();
        $request->session()->put(PreviewLock::SESSION_KEY, true);

        return redirect('/');
    }
}
