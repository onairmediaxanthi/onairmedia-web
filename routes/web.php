<?php

use App\Http\Controllers\PreviewUnlockController;
use Illuminate\Support\Facades\Route;

Route::post('/preview-unlock', PreviewUnlockController::class)
    ->middleware('throttle:5,1')
    ->name('preview.unlock');

Route::view('/', 'welcome')->name('home');
