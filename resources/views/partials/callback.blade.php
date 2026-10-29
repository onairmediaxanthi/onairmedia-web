{{-- «Να σας καλέσουμε»: ανοίγει από οποιοδήποτε κουμπί με $dispatch('open-callback') --}}
@php($siteKey = \App\Support\Turnstile::enabled() ? \App\Support\Turnstile::siteKey() : null)
@php($u = auth()->user())
<div x-data="{
        open: false, sending: false, done: false, errors: {}, widget: null,
        form: { first_name: '', last_name: '', mobile: '', vat: '', consent: false, company_url: '' },
        show() {
            this.open = true; this.done = false; this.errors = {};
            this.$nextTick(() => { this.$refs.first.focus(); this.robot(); });
        },
        robot() {
            @if ($siteKey)
            const render = () => { if (this.widget === null && window.turnstile) this.widget = turnstile.render(this.$refs.ts, { sitekey: '{{ $siteKey }}', language: 'el' }); else if (window.turnstile) turnstile.reset(this.widget); };
            if (window.turnstile) return render();
            const s = document.createElement('script'); s.src = 'https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit'; s.async = true; s.onload = render; document.head.appendChild(s);
            @endif
        },
        async send() {
            this.sending = true; this.errors = {};
            const body = { ...this.form, t: '{{ \App\Http\Controllers\CallbackRequestController::stamp() }}' };
            @if ($siteKey) body['cf-turnstile-response'] = window.turnstile ? turnstile.getResponse(this.widget) : ''; @endif
            try {
                const r = await fetch('{{ route('callback.store') }}', { method: 'POST', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }, body: JSON.stringify(body) });
                if (r.status === 422) { this.errors = (await r.json()).errors || {}; @if ($siteKey) if (window.turnstile) turnstile.reset(this.widget); @endif }
                else if (r.status === 429) { this.errors = { form: ['Πολλές προσπάθειες. Δοκιμάστε ξανά σε λίγα λεπτά.'] }; }
                else if (r.ok) { this.done = true; }
                else { this.errors = { form: ['Κάτι πήγε στραβά. Δοκιμάστε ξανά ή καλέστε μας.'] }; }
            } catch (e) { this.errors = { form: ['Δεν υπάρχει σύνδεση. Δοκιμάστε ξανά.'] }; }
            this.sending = false;
        },
     }"
     @open-callback.window="show()" @keydown.escape.window="open = false">

    @if (\App\Models\Setting::get('callback_floating', true))
        <button type="button" @click="show()"
                class="fixed bottom-24 right-4 z-40 flex items-center gap-2 rounded-full bg-glow px-4 py-3 font-bold text-night shadow-xl ring-4 ring-white/60 hover:brightness-105 sm:bottom-28 sm:right-6 dark:ring-night/60"
                aria-haspopup="dialog">
            <x-ui-icon name="phone" class="h-5 w-5" />
            <span class="max-sm:hidden">Να σας καλέσουμε;</span>
        </button>
    @endif

    <div x-show="open" x-cloak class="fixed inset-0 z-[100] flex items-end justify-center p-0 sm:items-center sm:p-4" role="dialog" aria-modal="true" aria-labelledby="cb-title">
        <div x-show="open" x-transition.opacity class="absolute inset-0 bg-night/60 backdrop-blur-sm" @click="open = false"></div>
        <div x-show="open" x-transition class="relative max-h-[92dvh] w-full max-w-md overflow-y-auto rounded-t-3xl bg-surface p-6 shadow-2xl sm:rounded-3xl sm:p-7" @click.stop>
            <button type="button" @click="open = false" class="absolute right-4 top-4 grid h-9 w-9 place-items-center rounded-full text-slate hover:bg-paper" aria-label="Κλείσιμο">✕</button>

            <template x-if="! done">
                <form @submit.prevent="send()" novalidate>
                    <span class="grid h-12 w-12 place-items-center rounded-2xl bg-onair/10 text-onair"><x-ui-icon name="phone" class="h-6 w-6" /></span>
                    <h2 id="cb-title" class="mt-4 text-2xl font-extrabold">Να σας καλέσουμε;</h2>
                    <p class="mt-1 text-slate">Αφήστε τα στοιχεία σας και θα σας ετοιμάσουμε εμείς την καμπάνια, εντός μίας εργάσιμης.</p>

                    <div class="mt-5 grid grid-cols-2 gap-3">
                        <div>
                            <label for="cb-first" class="block text-sm font-semibold">Όνομα</label>
                            <input id="cb-first" x-ref="first" x-model="form.first_name" autocomplete="given-name" maxlength="60" required class="mt-1 block w-full rounded-xl border border-line bg-surface px-3.5 py-2.5 text-[16px] outline-none focus:border-onair focus:ring-2 focus:ring-onair/25">
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400" x-text="errors.first_name?.[0]"></p>
                        </div>
                        <div>
                            <label for="cb-last" class="block text-sm font-semibold">Επίθετο</label>
                            <input id="cb-last" x-model="form.last_name" autocomplete="family-name" maxlength="60" required class="mt-1 block w-full rounded-xl border border-line bg-surface px-3.5 py-2.5 text-[16px] outline-none focus:border-onair focus:ring-2 focus:ring-onair/25">
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400" x-text="errors.last_name?.[0]"></p>
                        </div>
                        <div class="col-span-2 sm:col-span-1">
                            <label for="cb-mobile" class="block text-sm font-semibold">Κινητό</label>
                            <input id="cb-mobile" x-model="form.mobile" type="tel" inputmode="tel" autocomplete="tel" maxlength="20" placeholder="69…" required class="mt-1 block w-full rounded-xl border border-line bg-surface px-3.5 py-2.5 text-[16px] outline-none focus:border-onair focus:ring-2 focus:ring-onair/25">
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400" x-text="errors.mobile?.[0]"></p>
                        </div>
                        <div class="col-span-2 sm:col-span-1">
                            <label for="cb-vat" class="block text-sm font-semibold">ΑΦΜ επιχείρησης</label>
                            <input id="cb-vat" x-model="form.vat" inputmode="numeric" maxlength="12" required class="mt-1 block w-full rounded-xl border border-line bg-surface px-3.5 py-2.5 text-[16px] outline-none focus:border-onair focus:ring-2 focus:ring-onair/25">
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400" x-text="errors.vat?.[0]"></p>
                        </div>
                    </div>
                    {{-- Παγίδα για bots: κρυφό από ανθρώπους και αναγνώστες οθόνης --}}
                    <div class="absolute -left-[9999px] h-0 overflow-hidden" aria-hidden="true">
                        <label>Ιστοσελίδα <input x-model="form.company_url" tabindex="-1" autocomplete="off"></label>
                    </div>

                    <label class="mt-4 flex items-start gap-2.5 text-sm">
                        <input type="checkbox" x-model="form.consent" class="mt-0.5 h-4 w-4 accent-[var(--color-onair)]">
                        <span>Συμφωνώ να με καλέσετε για αυτό το αίτημα. <a href="{{ route('legal.privacy') }}" target="_blank" class="font-semibold text-onair hover:underline">Προσωπικά δεδομένα</a></span>
                    </label>
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400" x-text="errors.consent?.[0]"></p>

                    @if ($siteKey)<div class="mt-4 min-h-16" x-ref="ts"></div>@endif
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400" x-text="errors.robot?.[0] || errors.form?.[0]"></p>

                    <button type="submit" :disabled="sending" class="mt-5 flex w-full items-center justify-center gap-2 rounded-full bg-onair px-6 py-3.5 font-semibold text-white hover:bg-onair-dark disabled:opacity-60">
                        <span x-show="! sending">Καλέστε με</span><span x-show="sending" x-cloak>Αποστολή…</span>
                    </button>
                </form>
            </template>

            <template x-if="done">
                <div class="py-4 text-center">
                    <span class="mx-auto grid h-14 w-14 place-items-center rounded-full bg-emerald-500/15 text-emerald-600"><x-ui-icon name="check" class="h-7 w-7" /></span>
                    <h2 class="mt-4 text-2xl font-extrabold">Ευχαριστούμε!</h2>
                    <p class="mt-2 text-slate">Λάβαμε το αίτημά σας. Θα σας καλέσουμε στο <strong class="text-ink" x-text="form.mobile"></strong> εντός μίας εργάσιμης.</p>
                    <button type="button" @click="open = false" class="mt-6 rounded-full border border-line px-6 py-3 font-semibold hover:border-onair">Κλείσιμο</button>
                </div>
            </template>
        </div>
    </div>
</div>
