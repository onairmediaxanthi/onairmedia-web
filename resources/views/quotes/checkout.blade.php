<x-layouts.site :title="'Ταμείο · '.$quote->number">
    <section class="mx-auto max-w-3xl px-4 sm:px-6 py-12 sm:py-16">
        <a href="{{ route('quotes.show', $quote) }}" class="font-semibold text-slate hover:text-ink">← Πίσω στην προσφορά</a>
        <h1 class="mt-3 text-3xl sm:text-4xl font-extrabold tracking-tight">Ταμείο</h1>
        <p class="mt-2 text-slate">{{ $quote->number }} · {{ $quote->title }}</p>

        @if (session('status'))<p class="mt-6 rounded-xl border border-emerald-500/40 bg-emerald-500/10 px-4 py-3" role="status">{{ session('status') }}</p>@endif
        @if (session('error'))<p class="mt-6 rounded-xl border border-red-500/40 bg-red-500/10 px-4 py-3" role="alert">{{ session('error') }}</p>@endif

        @php($couponLine = collect($quote->items)->firstWhere('type', 'coupon'))
        <div class="mt-8 rounded-3xl border border-line bg-surface p-6 sm:p-8">
            <dl class="space-y-1.5">
                <div class="flex justify-between text-slate"><dt>Σύνολο χωρίς ΦΠΑ</dt><dd class="freq">{{ \App\Services\Quotes\QuoteCalculator::money((float) $quote->subtotal - (float) ($couponLine['amount'] ?? 0)) }}</dd></div>
                @if ($couponLine)
                    <div class="flex items-center justify-between gap-3 font-semibold text-onair">
                        <dt>{{ $couponLine['label'] }}</dt>
                        <dd class="flex items-center gap-3"><span class="freq">{{ \App\Services\Quotes\QuoteCalculator::money($couponLine['amount']) }}</span>
                            <form method="POST" action="{{ route('quotes.coupon.remove', $quote) }}">@csrf @method('DELETE')<button class="text-sm font-normal text-slate underline hover:text-ink">αφαίρεση</button></form>
                        </dd>
                    </div>
                @endif
                <div class="flex justify-between text-slate"><dt>ΦΠΑ {{ rtrim(rtrim((string) $quote->vat_rate, '0'), '.') }}%</dt><dd class="freq">{{ $quote->money('vat') }}</dd></div>
                <div class="flex items-baseline justify-between gap-4 border-t border-line pt-3"><dt class="text-lg font-semibold">Τελικό ποσό με ΦΠΑ</dt><dd class="text-3xl font-extrabold freq">{{ $quote->money('total') }}</dd></div>
            </dl>

            @unless ($couponLine)
                <form method="POST" action="{{ route('quotes.coupon', $quote) }}" class="mt-6 flex flex-wrap items-end gap-3">
                    @csrf
                    <div class="flex-1 min-w-48">
                        <label for="code" class="block text-sm font-semibold">Έχετε κουπόνι έκπτωσης;</label>
                        <input id="code" name="code" value="{{ old('code') }}" maxlength="40" autocomplete="off" placeholder="π.χ. KALOS-7FK2QM"
                               class="mt-1.5 block w-full rounded-xl border bg-surface px-4 py-3 text-[16px] uppercase tracking-wider outline-none focus:border-onair focus:ring-2 focus:ring-onair/25 @error('code') border-red-500 @else border-line @enderror">
                    </div>
                    <button class="rounded-full border-2 border-onair px-6 py-3 font-semibold text-onair hover:bg-onair hover:text-white">Εφαρμογή</button>
                    @error('code')<p class="basis-full text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                </form>
            @endunless
        </div>

        <form method="POST" action="{{ route('quotes.submit', $quote) }}" class="mt-6 space-y-6 rounded-3xl border border-line bg-surface p-6 sm:p-8">
            @csrf

            <div>
                <label for="notes" class="block text-sm font-semibold">Σημειώσεις για την ομάδα μας <span class="font-normal text-slate">(προαιρετικό)</span></label>
                <textarea id="notes" name="notes" rows="4" maxlength="2000" class="mt-1.5 block w-full rounded-xl border border-line bg-surface px-4 py-3 text-[16px] outline-none focus:border-onair focus:ring-2 focus:ring-onair/25"
                          placeholder="π.χ. προτιμώ πρωινές ώρες, το σποτ θα σας το στείλω την Παρασκευή, θέλω τιμολόγιο σε άλλη επωνυμία…">{{ old('notes') }}</textarea>
                @error('notes')<p class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
            </div>

            <fieldset>
                <legend class="text-sm font-semibold">Τρόπος πληρωμής</legend>
                <div class="mt-2 grid gap-3">
                    @foreach ($methods as $m)
                        <label class="flex cursor-pointer items-start gap-3 rounded-2xl border border-line p-4 has-[:checked]:border-onair has-[:checked]:bg-onair/5">
                            <input type="radio" name="payment_method" value="{{ $m }}" @checked(old('payment_method', 'bank') === $m) class="mt-1 h-4 w-4 accent-[var(--color-onair)]">
                            <span>
                                <span class="font-semibold">{{ \App\Models\Order::METHODS[$m] }}</span>
                                <span class="block text-sm text-slate">{{ [
                                    'bank' => 'Μόλις επιβεβαιώσουμε την παραγγελία, θα δείτε εδώ και στο email σας τον λογαριασμό και την αιτιολογία.',
                                    'office' => 'Περνάτε από το γραφείο μας μετά την επιβεβαίωση.',
                                    'credit' => 'Η καμπάνια ξεκινά με την επιβεβαίωση· εξοφλείτε βάσει τιμολογίου, όπως έχουμε συμφωνήσει.',
                                ][$m] }}</span>
                            </span>
                        </label>
                    @endforeach
                </div>
                @error('payment_method')<p class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
            </fieldset>

            <div class="rounded-2xl bg-paper p-5 text-[15px]">
                <p class="font-semibold">Πώς προχωράμε</p>
                <ol class="mt-2 list-decimal space-y-1 pl-5 text-slate">
                    <li>Ελέγχουμε τη διαθεσιμότητα και σας επιβεβαιώνουμε την παραγγελία, εντός μίας εργάσιμης.</li>
                    <li>Εξοφλείτε με τον τρόπο που διαλέξατε και μας στέλνετε (ή φτιάχνουμε εμείς) το υλικό.</li>
                    <li>Προγραμματίζουμε την καμπάνια. Σε κάθε βήμα σας ενημερώνουμε με email και το βλέπετε στο Studio σας.</li>
                </ol>
            </div>

            <label class="flex items-start gap-3">
                <input type="checkbox" name="accept" value="1" required class="mt-1 h-5 w-5 accent-[var(--color-onair)]">
                <span>Διάβασα και αποδέχομαι τους <a href="{{ route('legal.terms') }}" target="_blank" class="font-semibold text-onair hover:underline">όρους χρήσης</a> και ότι η καμπάνια ξεκινά μετά την επιβεβαίωση και την εξόφληση.</span>
            </label>
            @error('accept')<p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror

            <x-form.button class="w-full">Αποστολή παραγγελίας</x-form.button>
        </form>
    </section>
</x-layouts.site>
