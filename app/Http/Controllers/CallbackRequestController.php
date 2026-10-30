<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Mail\CallbackRequested;
use App\Models\Business;
use App\Models\CallbackRequest;
use App\Models\Setting;
use App\Models\User;
use App\Rules\GreekVat;
use App\Services\CustomerEvents;
use App\Support\Turnstile;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Throwable;

/** «Να με καλέσετε»: μικρή φόρμα από οποιοδήποτε σημείο του site */
class CallbackRequestController extends Controller
{
    /** Σφραγίδα χρόνου για να ξεχωρίζουν τα bots που συμπληρώνουν σε κλάσματα δευτερολέπτου */
    public static function stamp(): string
    {
        return Crypt::encryptString((string) now()->timestamp);
    }

    public function store(Request $request): JsonResponse
    {
        $input = $request->all();
        $input['mobile'] = self::normalizeMobile((string) $request->input('mobile'));
        $input['vat'] = preg_replace('/\D/', '', (string) $request->input('vat'));
        $request->merge($input);

        // Παγίδα για bots: κρυφό πεδίο που ο άνθρωπος δεν βλέπει, και ελάχιστος χρόνος συμπλήρωσης
        if (filled($request->input('company_url')) || ! self::humanSpeed($request->input('t'))) {
            Log::warning('Callback request blocked as bot', ['honeypot' => filled($request->input('company_url')), 'ip' => $request->ip()]);

            return response()->json(['ok' => true]); // δεν δίνουμε στοιχεία στο bot
        }

        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:60'],
            'last_name' => ['required', 'string', 'max:60'],
            'mobile' => ['required', 'regex:/^69\d{8}$/'],
            'vat' => ['required', new GreekVat],
            'consent' => ['accepted'],
        ], [
            'first_name.required' => 'Γράψτε το όνομά σας.',
            'last_name.required' => 'Γράψτε το επίθετό σας.',
            'mobile.required' => 'Γράψτε το κινητό σας.',
            'mobile.regex' => 'Γράψτε ένα ελληνικό κινητό, π.χ. 6941234567.',
            'vat.required' => 'Γράψτε το ΑΦΜ της επιχείρησης.',
            'consent.accepted' => 'Χρειάζεται να συμφωνήσετε να σας καλέσουμε.',
        ]);

        if (! Turnstile::verify($request->input('cf-turnstile-response'), $request->ip())) {
            throw ValidationException::withMessages(['robot' => 'Επιβεβαιώστε ότι δεν είστε ρομπότ.']);
        }

        $business = Business::query()->where('vat', $data['vat'])->first();
        $cb = CallbackRequest::query()->create([
            'first_name' => trim($data['first_name']), 'last_name' => trim($data['last_name']),
            'mobile' => $data['mobile'], 'vat' => $data['vat'],
            'business_id' => $business?->id, 'user_id' => $request->user()?->id,
            'page' => mb_substr((string) parse_url((string) $request->headers->get('referer'), PHP_URL_PATH), 0, 255) ?: null,
        ]);

        $this->notify($cb);
        if ($business?->user) {
            CustomerEvents::record('callback_requested', $business->user, null, ['mobile' => $cb->mobile]);
        }

        return response()->json(['ok' => true]);
    }

    protected function notify(CallbackRequest $cb): void
    {
        try {
            $staff = User::query()->whereIn('role', [UserRole::Admin->value, UserRole::Sales->value])->whereNull('disabled_at')->get();
            if ($staff->isNotEmpty()) {
                Notification::make()->title('Να τον καλέσουμε: '.$cb->fullName())->body($cb->mobile.' · ΑΦΜ '.$cb->vat)
                    ->icon('heroicon-o-phone')->iconColor('warning')
                    ->actions([Action::make('open')->label('Άνοιγμα')->url(url('/admin/aitimata/'.$cb->id.'/edit'))->markAsRead()])
                    ->sendToDatabase($staff);
            }
        } catch (Throwable $e) {
            Log::warning('Callback bell failed: '.$e->getMessage());
        }

        $raw = (string) (Setting::get('callback_emails') ?: '');
        $to = $raw !== '' ? array_values(array_filter(array_map('trim', preg_split('/[,;\s]+/', $raw)), fn ($e) => filter_var($e, FILTER_VALIDATE_EMAIL)))
            : CustomerEvents::recipients();
        if ($to) {
            try {
                Mail::to($to)->send(new CallbackRequested($cb));
            } catch (Throwable $e) {
                Log::warning('Callback email failed: '.$e->getMessage());
            }
        }
    }

    public static function normalizeMobile(string $raw): string
    {
        $d = preg_replace('/\D/', '', $raw);
        if (str_starts_with($d, '0030')) {
            $d = substr($d, 4);
        } elseif (str_starts_with($d, '30') && strlen($d) === 12) {
            $d = substr($d, 2);
        }

        return $d;
    }

    protected static function humanSpeed(?string $stamp): bool
    {
        try {
            $t = (int) Crypt::decryptString((string) $stamp);
        } catch (Throwable) {
            return false;
        }
        $age = now()->timestamp - $t;

        return $age >= 3 && $age <= 7 * 86400; // τουλάχιστον 3″ για να συμπληρωθεί, όχι παλιότερο από μια εβδομάδα
    }
}
