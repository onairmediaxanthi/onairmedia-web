<x-layouts.site :title="'Ταμείο · '.$quote->number">
    <section class="mx-auto max-w-3xl px-4 sm:px-6 py-12 sm:py-16">
        <a href="{{ route('quotes.show', $quote) }}" class="font-semibold text-slate hover:text-ink">← Πίσω στην προσφορά</a>
        <h1 class="mt-3 text-3xl sm:text-4xl font-extrabold tracking-tight">Ταμείο</h1>
        <p class="mt-2 text-slate">{{ $quote->number }} · {{ $quote->title }}</p>

        <form method="POST" action="{{ route('quotes.submit', $quote) }}" class="mt-8 space-y-6 rounded-3xl border border-line bg-surface p-6 sm:p-8">
            @csrf
            <div class="flex items-baseline justify-between gap-4 border-b border-line pb-5">
                <span class="text-lg font-semibold">Τελικό ποσό με ΦΠΑ</span>
                <span class="text-3xl font-extrabold freq">{{ $quote->money('total') }}</span>
            </div>

            <div>
                <label for="notes" class="block text-sm font-semibold">Σημειώσεις για την ομάδα μας <span class="font-normal text-slate">(προαιρετικό)</span></label>
                <textarea id="notes" name="notes" rows="4" maxlength="2000" class="mt-1.5 block w-full rounded-xl border border-line bg-surface px-4 py-3 text-[16px] outline-none focus:border-onair focus:ring-2 focus:ring-onair/25"
                          placeholder="π.χ. προτιμώ πρωινές ώρες, το σποτ θα σας το στείλω την Παρασκευή, θέλω τιμολόγιο σε άλλη επωνυμία…">{{ old('notes') }}</textarea>
                @error('notes')<p class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
            </div>

            <div class="rounded-2xl bg-paper p-5 text-[15px]">
                <p class="font-semibold">Πώς προχωράμε</p>
                <ol class="mt-2 list-decimal space-y-1 pl-5 text-slate">
                    <li>Λαμβάνουμε την παραγγελία και σας στέλνουμε επιβεβαίωση με το PDF.</li>
                    <li>Σας καλούμε εντός μίας εργάσιμης για το πρόγραμμα και το υλικό.</li>
                    <li>Πληρώνετε με κάρτα ή τραπεζική κατάθεση και η καμπάνια ξεκινά.</li>
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
