<x-layouts.site title="Το Studio μου — On Air Media Group">
    <section class="mx-auto max-w-5xl px-4 sm:px-6 py-12 sm:py-16">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="text-slate">Το Studio μου</p>
                <h1 class="text-3xl sm:text-4xl font-extrabold tracking-tight">{{ $business->displayName() }}</h1>
            </div>
            <form method="POST" action="{{ route('logout') }}">@csrf<button class="font-semibold text-slate hover:text-ink">Αποσύνδεση</button></form>
        </div>

        @if (session('status'))
            <p class="mt-6 rounded-xl border border-emerald-500/40 bg-emerald-500/10 px-4 py-3" role="status">{{ session('status') }}</p>
        @endif

        @unless ($user->hasVerifiedEmail())
            <div class="mt-6 flex flex-wrap items-center justify-between gap-4 rounded-2xl border border-amber-500/50 bg-amber-500/10 px-5 py-4">
                <p><span class="font-semibold">Επιβεβαιώστε το email σας</span> για να δείτε τιμές και να κλείσετε καμπάνια.</p>
                <form method="POST" action="{{ route('verification.send') }}">@csrf<button class="font-semibold text-onair hover:underline">Στείλτε ξανά τον σύνδεσμο</button></form>
            </div>
        @endunless

        <div class="mt-10 grid gap-6 lg:grid-cols-[1fr_340px]">
            <div class="space-y-6">
                <div class="rounded-2xl border border-line bg-surface p-6">
                    <h2 class="text-xl font-bold">Οι καμπάνιες μου</h2>
                    <p class="mt-2 text-slate">Δεν έχετε ακόμα καμπάνια. Σύντομα θα τη φτιάχνετε εδώ, στο Studio Καμπάνιας: σταθμοί, ώρες, ημερομηνίες και τιμή με μια ματιά.</p>
                    <a href="{{ route('home') }}#paketa" class="mt-4 inline-flex font-semibold text-onair hover:underline">Δείτε τα έτοιμα πακέτα</a>
                </div>
            </div>

            <aside class="space-y-6">
                <div class="rounded-2xl border border-line bg-surface p-6">
                    <div class="flex items-center justify-between gap-3">
                        <h2 class="text-lg font-bold">Στοιχεία επιχείρησης</h2>
                        <a href="{{ route('account.edit') }}" class="text-[15px] font-semibold text-onair hover:underline">Αλλαγή</a>
                    </div>
                    <dl class="mt-4 space-y-3 text-[15px]">
                        <div><dt class="text-slate text-sm">Επωνυμία</dt><dd>{{ $business->legal_name }}</dd></div>
                        <div><dt class="text-slate text-sm">ΑΦΜ</dt><dd class="freq">{{ $business->vat }}@if($business->tax_office) · {{ $business->tax_office }}@endif</dd></div>
                        @if ($business->gemi)<div><dt class="text-slate text-sm">ΓΕΜΗ</dt><dd class="freq">{{ $business->gemi }}</dd></div>@endif
                        <div><dt class="text-slate text-sm">Είδος</dt><dd>{{ $business->typeLabel() }}</dd></div>
                        <div><dt class="text-slate text-sm">Διεύθυνση</dt><dd>{{ $business->address }}, {{ $business->postal_code }} {{ $business->city }}</dd></div>
                        <div><dt class="text-slate text-sm">Επικοινωνία</dt><dd>{{ $business->contact_person }} · <span class="freq">{{ $business->mobile }}</span></dd></div>
                        <div><dt class="text-slate text-sm">Email</dt><dd class="break-all">{{ $user->email }}</dd></div>
                    </dl>
                </div>

                <form method="POST" action="{{ route('account.marketing') }}" class="rounded-2xl border border-line bg-surface p-6">
                    @csrf @method('PUT')
                    <h2 class="text-lg font-bold">Προσφορές στο email</h2>
                    @if ($business->marketing_consent_at)
                        <p class="mt-2 text-[15px] text-slate">Λαμβάνετε τις προσφορές μας.</p>
                        <input type="hidden" name="marketing" value="0">
                        <button class="mt-3 font-semibold text-slate hover:text-ink">Σταματήστε τις προσφορές</button>
                    @else
                        <p class="mt-2 text-[15px] text-slate">Μάθετε πρώτοι για εκπτώσεις και κουπόνια.</p>
                        <input type="hidden" name="marketing" value="1">
                        <button class="mt-3 font-semibold text-onair hover:underline">Θέλω προσφορές</button>
                    @endif
                </form>

                <details class="rounded-2xl border border-line bg-surface p-6" @if($errors->delete->any()) open @endif>
                    <summary class="cursor-pointer font-semibold text-slate">Διαγραφή λογαριασμού</summary>
                    <form method="POST" action="{{ route('account.destroy') }}" class="mt-4 space-y-4">
                        @csrf @method('DELETE')
                        <p class="text-[15px] text-slate">Διαγράφονται ο λογαριασμός και τα στοιχεία της επιχείρησης. Δεν αναιρείται.</p>
                        <div>
                            <label for="del-pass" class="block text-sm font-semibold">Ο κωδικός σας</label>
                            <input id="del-pass" type="password" name="password" required autocomplete="current-password" class="mt-1.5 block w-full rounded-xl border border-line bg-surface px-4 py-3">
                            @error('password', 'delete')<p class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                        </div>
                        <button class="rounded-full border border-red-500 px-5 py-2.5 font-semibold text-red-600 hover:bg-red-500/10 dark:text-red-400">Οριστική διαγραφή</button>
                    </form>
                </details>
            </aside>
        </div>
    </section>
</x-layouts.site>
