<x-layouts.site title="Εγγραφή επαγγελματία — On Air Media Group" description="Δωρεάν εγγραφή επιχείρησης: δείτε τιμές, κλείστε καμπάνια σε ραδιόφωνο, thrakitoday.gr και Οθόνη Πόλης.">
    <x-auth-card title="Εγγραφή επαγγελματία" intro="Δωρεάν και σε δύο λεπτά. Μετά βλέπετε τιμές, φτιάχνετε καμπάνια και πληρώνετε online." wide>
        @if ($errors->any())
            <div class="mb-6 rounded-xl border border-red-500/40 bg-red-500/10 px-4 py-3 text-[15px]" role="alert">
                Ελέγξτε τα πεδία με κόκκινο ({{ $errors->count() }}).
            </div>
        @endif

        <form method="POST" action="{{ route('register') }}" novalidate x-data="{ type: '{{ old('business_type') }}' }" class="space-y-10">
            @csrf
            {{-- Honeypot: κρυφό για ανθρώπους, τα bots το συμπληρώνουν --}}
            <div class="hidden" aria-hidden="true"><label>Ιστότοπος<input type="text" name="website_url" tabindex="-1" autocomplete="off"></label></div>

            <fieldset class="space-y-5">
                <legend class="text-xl font-bold">Η επιχείρηση</legend>
                <div class="grid gap-5 sm:grid-cols-2">
                    <x-form.field name="legal_name" label="Επωνυμία" required autocomplete="organization" hint="Όπως στην ΑΑΔΕ· θα μπει στο τιμολόγιο." class="sm:col-span-2" />
                    <x-form.field name="trade_name" label="Διακριτικός τίτλος" hint="Το όνομα που σας ξέρει ο κόσμος." />
                    <div>
                        <label for="f-business_type" class="block text-sm font-semibold">Είδος επιχείρησης</label>
                        <select id="f-business_type" name="business_type" x-model="type" required
                                class="mt-1.5 block w-full rounded-xl border bg-surface px-4 py-3 text-[16px] focus:border-onair focus:ring-2 focus:ring-onair/25 @error('business_type') border-red-500 @else border-line @enderror">
                            <option value="">Επιλέξτε…</option>
                            @foreach (\App\Models\Business::TYPES as $k => $v)
                                <option value="{{ $k }}" @selected(old('business_type') === $k)>{{ $v }}</option>
                            @endforeach
                        </select>
                        @error('business_type')<p class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                    </div>
                    <div x-show="type === 'other'" x-cloak class="sm:col-span-2">
                        <x-form.field name="business_type_other" label="Ποιο είδος;" required />
                    </div>
                    <x-form.field name="vat" label="ΑΦΜ" required inputmode="numeric" maxlength="11" autocomplete="off" hint="9 ψηφία· ελέγχεται αυτόματα." />
                    <x-form.field name="tax_office" label="ΔΟΥ" />
                    <x-form.field name="gemi" label="Αριθμός ΓΕΜΗ" inputmode="numeric" maxlength="14" />
                    <x-form.field name="website" label="Ιστοσελίδα" type="url" placeholder="https://" autocomplete="url" />
                </div>
            </fieldset>

            <fieldset class="space-y-5">
                <legend class="text-xl font-bold">Διεύθυνση & επικοινωνία</legend>
                <div class="grid gap-5 sm:grid-cols-6">
                    <x-form.field name="address" label="Οδός και αριθμός" required autocomplete="street-address" class="sm:col-span-6" />
                    <x-form.field name="city" label="Πόλη" required autocomplete="address-level2" class="sm:col-span-4" />
                    <x-form.field name="postal_code" label="Τ.Κ." required inputmode="numeric" maxlength="6" autocomplete="postal-code" class="sm:col-span-2" />
                    <x-form.field name="contact_person" label="Υπεύθυνος επικοινωνίας" required autocomplete="name" class="sm:col-span-6" />
                    <x-form.field name="mobile" label="Κινητό" required type="tel" inputmode="tel" autocomplete="tel" hint="Για γρήγορη επικοινωνία για την καμπάνια σας." class="sm:col-span-3" />
                    <x-form.field name="phone" label="Σταθερό" type="tel" inputmode="tel" class="sm:col-span-3" />
                </div>
            </fieldset>

            <fieldset class="space-y-5">
                <legend class="text-xl font-bold">Ο λογαριασμός σας</legend>
                <div class="grid gap-5 sm:grid-cols-2">
                    <x-form.field name="email" label="Email" type="email" required autocomplete="email" hint="Θα σας στείλουμε σύνδεσμο επιβεβαίωσης." class="sm:col-span-2" />
                    <x-form.field name="password" label="Κωδικός" type="password" required autocomplete="new-password" hint="Τουλάχιστον 10 χαρακτήρες, με κεφαλαία, μικρά και αριθμό." />
                    <x-form.field name="password_confirmation" label="Ξανά ο κωδικός" type="password" required autocomplete="new-password" />
                </div>
            </fieldset>

            <fieldset class="space-y-4">
                <legend class="sr-only">Συναινέσεις</legend>
                <label class="flex gap-3 items-start">
                    <input type="checkbox" name="terms" value="1" @checked(old('terms')) required class="mt-1 h-5 w-5 rounded border-line text-onair focus:ring-onair">
                    <span>Αποδέχομαι τους <a href="{{ route('legal.terms') }}" target="_blank" class="font-semibold text-onair underline">όρους χρήσης</a> και έχω διαβάσει την <a href="{{ route('legal.privacy') }}" target="_blank" class="font-semibold text-onair underline">πολιτική προστασίας δεδομένων</a>.</span>
                </label>
                @error('terms')<p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                <label class="flex gap-3 items-start">
                    <input type="checkbox" name="marketing" value="1" @checked(old('marketing')) class="mt-1 h-5 w-5 rounded border-line text-onair focus:ring-onair">
                    <span>Θέλω να λαμβάνω προσφορές προβολής στο email μου. <span class="text-slate">Προαιρετικό· το σταματάτε με ένα κλικ.</span></span>
                </label>
            </fieldset>

            <x-turnstile />
            <x-form.button class="w-full sm:w-auto">Δημιουργία λογαριασμού</x-form.button>
        </form>

        <x-slot:footer>Έχετε ήδη λογαριασμό; <a href="{{ route('login') }}" class="font-semibold text-onair hover:underline">Σύνδεση</a></x-slot:footer>
    </x-auth-card>
</x-layouts.site>
