<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\InviteController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PreviewUnlockController;
use App\Http\Controllers\QuoteController;
use App\Http\Controllers\StationController;
use App\Http\Controllers\UnsubscribeController;
use App\Http\Middleware\EnsureCustomer;
use Illuminate\Support\Facades\Route;

Route::post('/preview-unlock', PreviewUnlockController::class)
    ->middleware('throttle:5,1')
    ->name('preview.unlock');

Route::get('/', HomeController::class)->name('home');
Route::get('/radio/{station}', [StationController::class, 'show'])->name('stations.show');
Route::view('/prosfora', 'quotes.builder')->name('quote.builder');
Route::post('/aitima-epikoinonias', [\App\Http\Controllers\CallbackRequestController::class, 'store'])
    ->middleware('throttle:3,10')->name('callback.store');

// Πρόσκληση πελάτη που άνοιξε το προσωπικό (υπογεγραμμένο, λήγει)
Route::get('/energopoiisi/{user}', [InviteController::class, 'show'])->middleware(['signed', 'throttle:20,1'])->name('invite.accept');
Route::post('/energopoiisi/{user}', [InviteController::class, 'store'])->middleware(['signed', 'throttle:10,1']);

// Διαγραφή από τις μαζικές αποστολές (χωρίς σύνδεση, και POST ενός κλικ από Gmail/Yahoo)
Route::get('/aposyndromi/{token}', [UnsubscribeController::class, 'show'])->middleware('throttle:30,1')->name('campaign.unsubscribe');
Route::post('/aposyndromi/{token}', [UnsubscribeController::class, 'store'])->middleware('throttle:30,1');

// Από το email υπενθύμισης, χωρίς σύνδεση (υπογεγραμμένο link)
Route::match(['get', 'post'], '/prosfores/{quote}/xoris-ypenthymiseis', [QuoteController::class, 'remindersOff'])
    ->middleware(['signed', 'throttle:10,1'])->name('quotes.reminders-off');

// ---- Επισκέπτες ----
Route::middleware('guest')->group(function () {
    Route::get('/eggrafi', [RegisterController::class, 'create'])->name('register');
    Route::post('/eggrafi', [RegisterController::class, 'store'])->middleware('throttle:5,10');
    Route::get('/syndesi', [LoginController::class, 'create'])->name('login');
    Route::post('/syndesi', [LoginController::class, 'store'])->middleware('throttle:20,1');
    Route::get('/kodikos', [PasswordResetController::class, 'request'])->name('password.request');
    Route::post('/kodikos', [PasswordResetController::class, 'email'])->middleware('throttle:5,10')->name('password.email');
    Route::get('/kodikos/neos/{token}', [PasswordResetController::class, 'edit'])->name('password.reset');
    Route::post('/kodikos/neos', [PasswordResetController::class, 'update'])->middleware('throttle:10,10')->name('password.update');
});

// ---- Συνδεδεμένοι πελάτες ----
Route::middleware('auth')->group(function () {
    Route::post('/aposyndesi', [LoginController::class, 'destroy'])->name('logout');

    Route::get('/email/epivevaiosi', [EmailVerificationController::class, 'notice'])->name('verification.notice');
    Route::get('/email/epivevaiosi/{id}/{hash}', [EmailVerificationController::class, 'verify'])
        ->middleware(['signed', 'throttle:6,1'])->name('verification.verify');
    Route::post('/email/epivevaiosi', [EmailVerificationController::class, 'send'])
        ->middleware('throttle:3,10')->name('verification.send');

    Route::middleware(EnsureCustomer::class)->group(function () {
        Route::get('/to-studio-mou', [AccountController::class, 'show'])->name('account');
        Route::get('/to-studio-mou/stoixeia', [AccountController::class, 'edit'])->name('account.edit');
        Route::put('/to-studio-mou/stoixeia', [AccountController::class, 'update'])->name('account.update');
        Route::put('/to-studio-mou/newsletter', [AccountController::class, 'marketing'])->name('account.marketing');
        Route::delete('/to-studio-mou', [AccountController::class, 'destroy'])->middleware('throttle:5,10')->name('account.destroy');

        Route::get('/to-studio-mou/prosfores', [QuoteController::class, 'index'])->name('quotes.index');
        Route::get('/to-studio-mou/prosfores/{quote}', [QuoteController::class, 'show'])->name('quotes.show');
        Route::get('/to-studio-mou/prosfores/{quote}/pdf', [QuoteController::class, 'pdf'])->middleware('throttle:20,1')->name('quotes.pdf');
        Route::post('/to-studio-mou/prosfores/{quote}/ananeosi', [QuoteController::class, 'renew'])->middleware('throttle:10,1')->name('quotes.renew');
        Route::get('/to-studio-mou/prosfores/{quote}/tameio', [QuoteController::class, 'checkout'])->name('quotes.checkout');
        Route::post('/to-studio-mou/prosfores/{quote}/tameio', [QuoteController::class, 'submit'])->middleware('throttle:5,1')->name('quotes.submit');
        Route::delete('/to-studio-mou/prosfores/{quote}', [QuoteController::class, 'destroy'])->name('quotes.destroy');
        Route::post('/to-studio-mou/prosfores/{quote}/kouponi', [QuoteController::class, 'applyCoupon'])->middleware('throttle:10,1')->name('quotes.coupon');
        Route::delete('/to-studio-mou/prosfores/{quote}/kouponi', [QuoteController::class, 'removeCoupon'])->name('quotes.coupon.remove');
    });
});

// ---- Νομικά ----
Route::view('/oroi-xrisis', 'legal.terms')->name('legal.terms');
Route::view('/prostasia-dedomenon', 'legal.privacy')->name('legal.privacy');
