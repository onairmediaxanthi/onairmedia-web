<?php

use App\Services\NowPlaying;
use Illuminate\Support\Facades\Route;

// Χωρίς session/cookies: ελαφρύ, για τον player
Route::get('/now-playing', fn (NowPlaying $np) => response()
    ->json($np->all())
    ->header('Cache-Control', 'public, max-age=15'))
    ->middleware('throttle:60,1')
    ->name('api.now-playing');
