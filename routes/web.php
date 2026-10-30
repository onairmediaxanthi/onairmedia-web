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
    ->middleware('throttle:5,1,preview.unlock')
    ->name('preview.unlock');

Route::get('/', HomeController::class)->name('home');
Route::get('/radio/{station}', [StationController::class, 'show'])->name('stations.show');
Route::view('/prosfora', 'quotes.builder')->name('quote.builder');
Route::post('/aitima-epikoinonias', [\App\Http\Controllers\CallbackRequestController::class, 'store'])
    ->middleware('throttle:3,10,callback.store')->name('callback.store');

// Πρόσκληση πελάτη που άνοιξε το προσωπικό (υπογεγραμμένο, λήγει)
Route::get('/energopoiisi/{user}', [InviteController::class, 'show'])->middleware(['signed', 'throttle:20,1,invite.accept'])->name('invite.accept');
Route::post('/energopoiisi/{user}', [InviteController::class, 'store'])->middleware(['signed', 'throttle:10,1,invite.store']);

// Διαγραφή από τις μαζικές αποστολές (χωρίς σύνδεση, και POST ενός κλικ από Gmail/Yahoo)
Route::get('/aposyndromi/{token}', [UnsubscribeController::class, 'show'])->middleware('throttle:30,1,campaign.unsubscribe')->name('campaign.unsubscribe');
Route::post('/aposyndromi/{token}', [UnsubscribeController::class, 'store'])->middleware('throttle:30,1,campaign.unsubscribe.store');

// Από το email υπενθύμισης, χωρίς σύνδεση (υπογεγραμμένο link)
Route::match(['get', 'post'], '/prosfores/{quote}/xoris-ypenthymiseis', [QuoteController::class, 'remindersOff'])
    ->middleware(['signed', 'throttle:10,1,quotes.reminders-off'])->name('quotes.reminders-off');

// ---- Επισκέπτες ----
Route::middleware('guest')->group(function () {
    Route::get('/eggrafi', [RegisterController::class, 'create'])->name('register');
    Route::post('/eggrafi', [RegisterController::class, 'store'])->middleware('throttle:5,10,register.store');
    Route::get('/syndesi', [LoginController::class, 'create'])->name('login');
    Route::post('/syndesi', [LoginController::class, 'store'])->middleware('throttle:20,1,login.store');
    Route::get('/kodikos', [PasswordResetController::class, 'request'])->name('password.request');
    Route::post('/kodikos', [PasswordResetController::class, 'email'])->middleware('throttle:5,10,password.email')->name('password.email');
    Route::get('/kodikos/neos/{token}', [PasswordResetController::class, 'edit'])->name('password.reset');
    Route::post('/kodikos/neos', [PasswordResetController::class, 'update'])->middleware('throttle:10,10,password.update')->name('password.update');
});

// ---- Συνδεδεμένοι πελάτες ----
Route::middleware('auth')->group(function () {
    Route::post('/aposyndesi', [LoginController::class, 'destroy'])->name('logout');

    Route::get('/email/epivevaiosi', [EmailVerificationController::class, 'notice'])->name('verification.notice');
    Route::get('/email/epivevaiosi/{id}/{hash}', [EmailVerificationController::class, 'verify'])
        ->middleware(['signed', 'throttle:6,1,verification.verify'])->name('verification.verify');
    Route::post('/email/epivevaiosi', [EmailVerificationController::class, 'send'])
        ->middleware('throttle:3,10,verification.send')->name('verification.send');

    Route::middleware(EnsureCustomer::class)->group(function () {
        Route::get('/to-studio-mou', [AccountController::class, 'show'])->name('account');
        Route::get('/to-studio-mou/stoixeia', [AccountController::class, 'edit'])->name('account.edit');
        Route::put('/to-studio-mou/stoixeia', [AccountController::class, 'update'])->name('account.update');
        Route::put('/to-studio-mou/newsletter', [AccountController::class, 'marketing'])->name('account.marketing');
        Route::delete('/to-studio-mou', [AccountController::class, 'destroy'])->middleware('throttle:5,10,account.destroy')->name('account.destroy');

        Route::get('/to-studio-mou/prosfores', [QuoteController::class, 'index'])->name('quotes.index');
        Route::get('/to-studio-mou/prosfores/{quote}', [QuoteController::class, 'show'])->name('quotes.show');
        Route::get('/to-studio-mou/prosfores/{quote}/pdf', [QuoteController::class, 'pdf'])->middleware('throttle:20,1,quotes.pdf')->name('quotes.pdf');
        Route::post('/to-studio-mou/prosfores/{quote}/ananeosi', [QuoteController::class, 'renew'])->middleware('throttle:10,1,quotes.renew')->name('quotes.renew');
        Route::get('/to-studio-mou/prosfores/{quote}/tameio', [QuoteController::class, 'checkout'])->name('quotes.checkout');
        Route::post('/to-studio-mou/prosfores/{quote}/tameio', [QuoteController::class, 'submit'])->middleware('throttle:5,1,quotes.submit')->name('quotes.submit');
        Route::delete('/to-studio-mou/prosfores/{quote}', [QuoteController::class, 'destroy'])->name('quotes.destroy');
        Route::post('/to-studio-mou/prosfores/{quote}/kouponi', [QuoteController::class, 'applyCoupon'])->middleware('throttle:10,1,quotes.coupon')->name('quotes.coupon');
        Route::delete('/to-studio-mou/prosfores/{quote}/kouponi', [QuoteController::class, 'removeCoupon'])->name('quotes.coupon.remove');

        Route::get('/to-studio-mou/paraggelies', [\App\Http\Controllers\OrderController::class, 'index'])->name('orders.index');
        Route::get('/to-studio-mou/paraggelies/{order}', [\App\Http\Controllers\OrderController::class, 'show'])->name('orders.show');
        Route::post('/to-studio-mou/paraggelies/{order}/katathesi', [\App\Http\Controllers\OrderController::class, 'deposit'])->middleware('throttle:5,1,orders.deposit')->name('orders.deposit');
        Route::post('/to-studio-mou/paraggelies/{order}/yliko', [\App\Http\Controllers\OrderController::class, 'upload'])->middleware('throttle:20,1,orders.upload')->name('orders.upload');
        Route::post('/to-studio-mou/paraggelies/{order}/yliko/oloklirosi', [\App\Http\Controllers\OrderController::class, 'materialsDone'])->middleware('throttle:5,1,orders.materials-done')->name('orders.materials-done');
        Route::get('/to-studio-mou/paraggelies/{order}/yliko/{file}', [\App\Http\Controllers\OrderController::class, 'file'])->middleware('throttle:60,1,orders.file')->name('orders.file');
        Route::get('/to-studio-mou/paraggelies/{order}/timologio', [\App\Http\Controllers\OrderController::class, 'invoice'])->middleware('throttle:20,1,orders.invoice')->name('orders.invoice');
    });
});

// ---- Νομικά ----
Route::view('/oroi-xrisis', 'legal.terms')->name('legal.terms');
Route::view('/prostasia-dedomenon', 'legal.privacy')->name('legal.privacy');

// ---- Αρχεία παραγγελιών για το προσωπικό (ιδιωτικός δίσκος, μόνο με σύνδεση διαχείρισης) ----
Route::get('/diaxeirisi-arxeia/paraggelia/{file}', function (\App\Models\OrderFile $file) {
    abort_unless(\Illuminate\Support\Facades\Auth::guard('admin')->user()?->canAccessArea('sales'), 403);
    abort_unless(\Illuminate\Support\Facades\Storage::disk('local')->exists($file->path), 404);

    return \Illuminate\Support\Facades\Storage::disk('local')->download($file->path, $file->name, ['Cache-Control' => 'private, no-store']);
})->middleware('auth:admin')->name('admin.order-file');

// Προβολή του email που στάλθηκε στον πελάτη για ένα βήμα της παραγγελίας
Route::get('/diaxeirisi-arxeia/email/{event}', function (\App\Models\OrderEvent $event) {
    abort_unless(\Illuminate\Support\Facades\Auth::guard('admin')->user()?->canAccessArea('sales'), 403);
    abort_if($event->internal, 404);

    return response((new \App\Mail\OrderUpdated($event->order, $event))->render())->header('Cache-Control', 'private, no-store');
})->middleware('auth:admin')->name('admin.order-email');

// Προεπισκόπηση PDF προσφοράς σε οποιοδήποτε σχέδιο (για επιλογή σχεδίου από τη διαχείριση)
Route::get('/diaxeirisi-arxeia/pdf/{quoteId}/{design}', function (int $quoteId, string $design) {
    abort_unless(\Illuminate\Support\Facades\Auth::guard('admin')->user()?->canAccessArea('sales'), 403);
    abort_unless(isset(\App\Services\Quotes\QuotePdf::DESIGNS[$design]), 404);
    $quote = \App\Models\Quote::withTrashed()->findOrFail($quoteId);

    return response(\App\Services\Quotes\QuotePdf::render($quote, $design), 200, [
        'Content-Type' => 'application/pdf',
        'Content-Disposition' => 'inline; filename="'.$design.'-'.\App\Services\Quotes\QuotePdf::filename($quote).'"',
        'Cache-Control' => 'private, no-store',
    ]);
})->whereNumber('quoteId')->middleware('auth:admin')->name('admin.quote-pdf');

