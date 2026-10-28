<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\PreviewUnlockController;
use App\Http\Controllers\StationController;
use Illuminate\Support\Facades\Route;

Route::post('/preview-unlock', PreviewUnlockController::class)
    ->middleware('throttle:5,1')
    ->name('preview.unlock');

Route::get('/', HomeController::class)->name('home');
Route::get('/radio/{station}', [StationController::class, 'show'])->name('stations.show');
