@php
    $m = fn ($v) => \App\Services\Quotes\QuoteCalculator::money($v);
    $chip = 'inline-flex items-center rounded-full border border-line bg-surface px-4 py-2 text-[15px] font-medium transition-colors hover:border-onair peer-checked:border-onair peer-checked:bg-onair peer-checked:text-white peer-focus-visible:ring-2 peer-focus-visible:ring-glow';
    $card = 'block h-full rounded-2xl border-2 border-line bg-surface p-5 transition-colors hover:border-onair/60 peer-checked:border-onair peer-checked:bg-onair/5 peer-focus-visible:ring-2 peer-focus-visible:ring-glow';
    $input = 'mt-1.5 block w-full rounded-xl border border-line bg-surface px-4 py-3 text-[16px] outline-none focus:border-onair focus:ring-2 focus:ring-onair/25';
    $small = 'block w-full rounded-lg border border-line bg-surface px-3 py-2 text-[15px] outline-none focus:border-onair focus:ring-2 focus:ring-onair/25';
    $steps = [1 => 'Στόχος', 2 => 'Ποσό & διάρκεια', 3 => 'Κοινό', 4 => 'Μέσα'];
    $registerUrl = auth()->check() ? route('verification.notice') : route('register');
@endphp
<div class="mx-auto max-w-6xl px-4 sm:px-6 py-10 sm:py-14">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="font-semibold text-onair">Studio Καμπάνιας</p>
            <h1 class="mt-1 text-3xl sm:text-4xl font-extrabold tracking-tight">
                @switch($mode)
                    @case('wizard') {{ $showResults ? 'Οι προτάσεις μας για εσάς' : 'Πείτε μας τι χρειάζεστε' }} @break
                    @case('manual') Φτιάξτε την καμπάνια σας @break
                    @case('packages') Έτοιμα πακέτα @break
                    @default Βγάλτε προσφορά σε δύο λεπτά
                @endswitch
            </h1>
        </div>
        @if ($mode !== 'start' && ! $editing)
            <button type="button" wire:click="choose('start')" class="font-semibold text-slate hover:text-ink">← Αλλαγή τρόπου</button>
        @endif
    </div>

    @if ($error)
        <p class="mt-6 rounded-xl border border-red-500/40 bg-red-500/10 px-4 py-3 text-red-700 dark:text-red-300" role="alert">{{ $error }}</p>
    @endif

    @if (! $canSee)
        <div class="mt-6 flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-amber-500/50 bg-amber-500/10 px-5 py-4">
            <p>Οι τιμές εμφανίζονται σε επαγγελματίες με <span class="font-semibold">{{ auth()->check() ? 'επιβεβαιωμένο email' : 'δωρεάν λογαριασμό' }}</span>. Απαντήστε στις ερωτήσεις και θα τις κρατήσουμε για μετά.</p>
            <a href="{{ $registerUrl }}" class="font-semibold text-onair hover:underline">{{ auth()->check() ? 'Επιβεβαίωση email' : 'Δωρεάν εγγραφή' }}</a>
        </div>
    @endif

    {{-- ================= Επιλογή τρόπου ================= --}}
    @if ($mode === 'start')
        <div class="mt-10 grid gap-5 md:grid-cols-3">
            <button type="button" wire:click="choose('wizard')" class="group relative text-left rounded-3xl bg-night p-7 text-white hover:ring-4 hover:ring-onair/40">
                <span class="absolute right-5 top-5 rounded-full bg-glow px-3 py-1 text-xs font-bold text-night">Προτείνεται</span>
                <svg class="h-10 w-10 text-glow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M12 3l1.9 5.8H20l-4.9 3.6 1.9 5.8L12 14.6l-5 3.6 1.9-5.8L4 8.8h6.1z"/></svg>
                <span class="mt-5 block text-2xl font-extrabold">Βοηθήστε με να διαλέξω</span>
                <span class="mt-2 block text-white/75">Τέσσερις ερωτήσεις: στόχος, ποσό, κοινό, μέσα. Σας προτείνουμε έως τρεις συνδυασμούς μέσα στο ποσό σας.</span>
                <span class="mt-6 inline-flex font-semibold text-glow group-hover:underline">Ξεκινήστε →</span>
            </button>
            <button type="button" wire:click="choose('manual')" class="group text-left rounded-3xl border-2 border-line bg-surface p-7 hover:border-onair">
                <svg class="h-10 w-10 text-onair" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M4 21v-7M4 10V3M12 21v-9M12 8V3M20 21v-5M20 12V3M1 14h6M9 8h6M17 16h6"/></svg>
                <span class="mt-5 block text-2xl font-extrabold">Θα το φτιάξω μόνος μου</span>
                <span class="mt-2 block text-slate">Διαλέξτε σταθμούς, μεταδόσεις, θέσεις banner, πινακίδα και ημέρες. Η τιμή αλλάζει μπροστά σας.</span>
                <span class="mt-6 inline-flex font-semibold text-onair group-hover:underline">Προς τις επιλογές →</span>
            </button>
            <button type="button" wire:click="choose('packages')" class="group text-left rounded-3xl border-2 border-line bg-surface p-7 hover:border-onair">
                <svg class="h-10 w-10 text-onair" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M21 8l-9-5-9 5 9 5 9-5zM3 8v8l9 5 9-5V8M12 13v8"/></svg>
                <span class="mt-5 block text-2xl font-extrabold">Έτοιμα πακέτα</span>
                <span class="mt-2 block text-slate">Δοκιμασμένοι συνδυασμοί και προσφορές της περιόδου, με μία κίνηση.</span>
                <span class="mt-6 inline-flex font-semibold text-onair group-hover:underline">Δείτε τα πακέτα →</span>
            </button>
        </div>
        <div class="mt-5 flex flex-wrap items-center justify-between gap-4 rounded-3xl border-2 border-dashed border-line p-6">
            <div>
                <p class="text-lg font-bold">Δεν θέλετε να ασχοληθείτε;</p>
                <p class="text-slate">Αφήστε μας το κινητό σας και θα σας καλέσουμε εμείς να τη φτιάξουμε μαζί.</p>
            </div>
            <button type="button" x-data @click="$dispatch('open-callback')" class="inline-flex items-center gap-2 rounded-full bg-glow px-6 py-3 font-bold text-night hover:brightness-105">
                <x-ui-icon name="phone" class="h-5 w-5" /> Να με καλέσετε
            </button>
        </div>
        <p class="mt-8 text-slate">Κάθε προσφορά αποθηκεύεται στο Studio σας. Την κατεβάζετε σε PDF, την αλλάζετε ή την προχωράτε όποτε θέλετε, χωρίς δέσμευση.</p>
    @endif

    {{-- ================= Οδηγός ================= --}}
    @if ($mode === 'wizard')
        <ol class="mt-8 grid grid-cols-4 gap-2" aria-label="Βήματα">
            @foreach ($steps as $n => $label)
                <li>
                    <button type="button" @if($n < $step || $showResults) wire:click="goToStep({{ $n }})" @else disabled @endif
                            class="w-full text-left" @if($n === $step && ! $showResults) aria-current="step" @endif>
                        <span class="block h-1.5 rounded-full {{ $n <= $step || $showResults ? 'bg-onair' : 'bg-line' }}"></span>
                        <span class="mt-2 block text-sm {{ $n === $step && ! $showResults ? 'font-bold text-ink' : 'text-slate' }}"><span class="max-sm:hidden">{{ $n }}. </span>{{ $label }}</span>
                    </button>
                </li>
            @endforeach
        </ol>

        @unless ($showResults)
            <form wire:submit="next" class="mt-8 rounded-3xl border border-line bg-surface p-6 sm:p-8">
                @if ($step === 1)
                    <fieldset>
                        <legend class="text-xl font-bold">Τι θέλετε να πετύχετε;</legend>
                        <div class="mt-5 grid gap-3 sm:grid-cols-2">
                            @foreach (\App\Services\Quotes\Recommender::GOALS as $key => $g)
                                <label class="cursor-pointer">
                                    <input type="radio" wire:model="goal" value="{{ $key }}" class="peer sr-only">
                                    <span class="{{ $card }}">
                                        <span class="block text-lg font-bold">{{ $g['label'] }}</span>
                                        <span class="mt-1 block text-slate">{{ $g['text'] }}</span>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    </fieldset>
                @endif

                @if ($step === 2)
                    <div class="grid gap-8 md:grid-cols-2">
                        <div>
                            <label for="w-budget" class="text-xl font-bold">Πόσα θέλετε να διαθέσετε;</label>
                            <p class="mt-1 text-slate">Συνολικό ποσό με ΦΠΑ. Μια εκτίμηση αρκεί.</p>
                            <div class="relative mt-4">
                                <input id="w-budget" type="number" inputmode="decimal" min="50" max="200000" step="10" wire:model="budget" class="{{ $input }} pr-12 text-2xl font-bold freq">
                                <span class="pointer-events-none absolute right-4 top-1/2 mt-0.5 -translate-y-1/2 text-xl text-slate">€</span>
                            </div>
                            @error('budget')<p class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                            <div class="mt-3 flex flex-wrap gap-2">
                                @foreach ([200, 500, 1000, 2000, 5000] as $b)
                                    <button type="button" wire:click="$set('budget', {{ $b }})" class="rounded-full border px-3 py-1.5 text-sm font-semibold {{ (float) $budget == $b ? 'border-onair bg-onair text-white' : 'border-line hover:border-onair' }}">{{ number_format($b, 0, ',', '.') }} €</button>
                                @endforeach
                            </div>
                        </div>
                        <div>
                            <p class="text-xl font-bold" id="w-days-label">Για πόσο καιρό;</p>
                            <div class="mt-4 flex flex-wrap gap-2" role="group" aria-labelledby="w-days-label">
                                @foreach ([7 => '1 εβδομάδα', 14 => '2 εβδομάδες', 30 => '1 μήνας', 60 => '2 μήνες', 90 => '3 μήνες'] as $d => $l)
                                    <button type="button" wire:click="$set('days', {{ $d }})" class="rounded-full border px-4 py-2 font-semibold {{ (int) $days === $d ? 'border-onair bg-onair text-white' : 'border-line hover:border-onair' }}">{{ $l }}</button>
                                @endforeach
                            </div>
                            <div class="mt-4 grid grid-cols-2 gap-3">
                                <div>
                                    <label for="w-days" class="text-sm font-semibold">Ή ακριβώς (ημέρες)</label>
                                    <input id="w-days" type="number" min="1" max="365" wire:model.blur="days" class="{{ $input }}">
                                    @error('days')<p class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                                </div>
                                <div>
                                    <label for="w-start" class="text-sm font-semibold">Έναρξη</label>
                                    <input id="w-start" type="date" min="{{ today()->format('Y-m-d') }}" wire:model="startsOn" class="{{ $input }}">
                                    @error('startsOn')<p class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                @if ($step === 3)
                    <p class="text-xl font-bold">Ποιους θέλετε να φτάσετε;</p>
                    <p class="mt-1 text-slate">Διαλέξτε όσα ισχύουν. Αν δεν είστε σίγουροι, προχωρήστε: θα προτείνουμε για όλο το κοινό.</p>
                    <div class="mt-6 space-y-6">
                        <fieldset>
                            <legend class="font-semibold">Ηλικίες</legend>
                            <div class="mt-3 flex flex-wrap gap-2">
                                @foreach (\App\Support\Audience::GROUPS['ages']['options'] as $k => $l)
                                    <label class="cursor-pointer"><input type="checkbox" wire:model="ages" value="{{ $k }}" class="peer sr-only"><span class="{{ $chip }}">{{ $l }}</span></label>
                                @endforeach
                            </div>
                        </fieldset>
                        <fieldset>
                            <legend class="font-semibold">Κυρίως</legend>
                            <div class="mt-3 flex flex-wrap gap-2">
                                <label class="cursor-pointer"><input type="radio" wire:model="gender" value="" class="peer sr-only"><span class="{{ $chip }}">Όλοι</span></label>
                                <label class="cursor-pointer"><input type="radio" wire:model="gender" value="female" class="peer sr-only"><span class="{{ $chip }}">Γυναίκες</span></label>
                                <label class="cursor-pointer"><input type="radio" wire:model="gender" value="male" class="peer sr-only"><span class="{{ $chip }}">Άνδρες</span></label>
                            </div>
                        </fieldset>
                        <fieldset>
                            <legend class="font-semibold">Ποιοι είναι οι πελάτες σας;</legend>
                            <div class="mt-3 flex flex-wrap gap-2">
                                @foreach (\App\Support\Audience::GROUPS['profiles']['options'] as $k => $l)
                                    <label class="cursor-pointer"><input type="checkbox" wire:model="profiles" value="{{ $k }}" class="peer sr-only"><span class="{{ $chip }}">{{ $l }}</span></label>
                                @endforeach
                            </div>
                        </fieldset>
                    </div>
                @endif

                @if ($step === 4)
                    <fieldset>
                        <legend class="text-xl font-bold">Πού θέλετε να εμφανιστείτε;</legend>
                        <div class="mt-5 grid gap-3 sm:grid-cols-3">
                            @foreach (['radio' => ['Ραδιόφωνο', 'Σποτ στους σταθμούς μας'], 'news' => ['thrakitoday.gr', 'Banner στο ειδησεογραφικό site'], 'led' => ['Πινακίδα LED', 'Γραφικό στην οθόνη της πόλης']] as $k => [$l, $t])
                                <label class="cursor-pointer">
                                    <input type="checkbox" wire:model="media" value="{{ $k }}" class="peer sr-only">
                                    <span class="{{ $card }}"><span class="block text-lg font-bold">{{ $l }}</span><span class="mt-1 block text-slate">{{ $t }}</span></span>
                                </label>
                            @endforeach
                        </div>
                        @error('media')<p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                    </fieldset>
                    <div class="mt-8 grid gap-8 md:grid-cols-2">
                        <fieldset>
                            <legend class="font-semibold">Έχετε έτοιμο διαφημιστικό υλικό;</legend>
                            <div class="mt-3 flex flex-wrap gap-2">
                                <label class="cursor-pointer"><input type="radio" wire:model="material" value="ready" class="peer sr-only"><span class="{{ $chip }}">Ναι, το έχω</span></label>
                                <label class="cursor-pointer"><input type="radio" wire:model="material" value="need" class="peer sr-only"><span class="{{ $chip }}">Όχι, φτιάξτε το εσείς</span></label>
                            </div>
                        </fieldset>
                        <div>
                            <label for="w-sec" class="font-semibold">Διάρκεια ραδιοφωνικού σποτ</label>
                            <select id="w-sec" wire:model="seconds" class="{{ $input }}">
                                @foreach ([15, 20, 30, 45, 60] as $s)<option value="{{ $s }}">{{ $s }} δευτερόλεπτα</option>@endforeach
                            </select>
                        </div>
                    </div>
                @endif

                <div class="mt-8 flex items-center justify-between gap-4 border-t border-line pt-6">
                    <button type="button" wire:click="back" class="font-semibold text-slate hover:text-ink">← Πίσω</button>
                    <x-form.button wire:loading.attr="disabled">
                        {{ $step < 4 ? 'Επόμενο' : ($canSee ? 'Δείτε τις προτάσεις' : 'Δείτε τις προτάσεις (δωρεάν εγγραφή)') }}
                    </x-form.button>
                </div>
            </form>
        @else
            <div class="mt-8 flex flex-wrap gap-x-6 gap-y-2 text-slate">
                <span>{{ \App\Services\Quotes\Recommender::GOALS[$goal]['label'] ?? '' }}</span>
                <span>έως <strong class="text-ink freq">{{ number_format((float) $budget, 0, ',', '.') }} €</strong> με ΦΠΑ</span>
                <span><strong class="text-ink">{{ $days }}</strong> ημέρες</span>
                <button type="button" wire:click="goToStep(2)" class="font-semibold text-onair hover:underline">Αλλαγή απαντήσεων</button>
            </div>

            @if (! $proposals)
                <div class="mt-8 rounded-3xl border border-line bg-surface p-8">
                    <h2 class="text-xl font-bold">Δεν χωράει ακόμα πρόταση σε αυτό το ποσό</h2>
                    @if ($minBudget)
                        <p class="mt-2 text-slate">Για {{ $days }} ημέρες χρειάζεται περίπου <strong class="text-ink">{{ number_format($minBudget, 0, ',', '.') }} €</strong> με ΦΠΑ. Δοκιμάστε μεγαλύτερο ποσό ή λιγότερες ημέρες.</p>
                    @else
                        <p class="mt-2 text-slate">Ο τιμοκατάλογος ενημερώνεται αυτές τις μέρες. Καλέστε μας και θα σας ετοιμάσουμε προσφορά αμέσως@if(\App\Models\Setting::get('phone')) στο <a class="font-semibold text-onair" href="tel:{{ preg_replace('/[^0-9+]/', '', \App\Models\Setting::get('phone')) }}">{{ \App\Models\Setting::get('phone') }}</a>@endif.</p>
                    @endif
                    <button type="button" wire:click="goToStep(2)" class="mt-5 font-semibold text-onair hover:underline">Αλλαγή ποσού ή διάρκειας</button>
                </div>
            @else
                <div class="mt-8 grid gap-5 {{ [1 => 'lg:grid-cols-1 lg:max-w-lg', 2 => 'lg:grid-cols-2', 3 => 'lg:grid-cols-3'][min(3, count($proposals))] }}">
                    @foreach ($proposals as $i => $p)
                        <article class="flex flex-col rounded-3xl border-2 {{ $p['key'] === 'combo' ? 'border-onair shadow-lg' : 'border-line' }} bg-surface p-6">
                            <div class="flex items-center justify-between gap-3">
                                <h2 class="text-2xl font-extrabold">{{ $p['title'] }}</h2>
                                @if ($p['key'] === 'combo')<span class="rounded-full bg-onair px-3 py-1 text-xs font-bold text-white">Η πρότασή μας</span>@endif
                            </div>
                            <p class="mt-2 text-slate">{{ $p['text'] }}</p>
                            <ul class="mt-5 divide-y divide-line border-y border-line">
                                @foreach ($p['priced']['lines'] as $line)
                                    <li class="flex items-start justify-between gap-3 py-3">
                                        <span><span class="block font-semibold">{{ $line['label'] }}</span>@if($line['detail'])<span class="block text-sm text-slate">{{ $line['detail'] }}</span>@endif</span>
                                        <span class="shrink-0 font-semibold freq">{{ $m($line['amount']) }}</span>
                                    </li>
                                @endforeach
                            </ul>
                            @if ($p['reasons'])
                                <ul class="mt-4 space-y-1 text-sm text-slate">
                                    @foreach ($p['reasons'] as $r)<li class="flex gap-2"><span class="text-onair" aria-hidden="true">●</span>{{ $r }}</li>@endforeach
                                </ul>
                            @endif
                            <dl class="mt-auto pt-6 space-y-1">
                                <div class="flex justify-between text-slate"><dt>Χωρίς ΦΠΑ</dt><dd class="freq">{{ $m($p['priced']['subtotal']) }}</dd></div>
                                <div class="flex justify-between text-slate"><dt>ΦΠΑ {{ rtrim(rtrim(number_format($p['priced']['vat_rate'], 2, ',', ''), '0'), ',') }}%</dt><dd class="freq">{{ $m($p['priced']['vat']) }}</dd></div>
                                <div class="flex justify-between text-xl font-extrabold"><dt>Σύνολο</dt><dd class="freq">{{ $m($p['priced']['total']) }}</dd></div>
                            </dl>
                            <div class="mt-5 flex flex-col gap-2">
                                <x-form.button type="button" wire:click="saveProposal({{ $i }})" wire:loading.attr="disabled">Αποθήκευση προσφοράς</x-form.button>
                                <button type="button" wire:click="customize({{ $i }})" class="rounded-full border border-line px-5 py-3 font-semibold hover:border-onair">Θέλω αλλαγές</button>
                            </div>
                        </article>
                    @endforeach
                </div>
                <p class="mt-6 text-sm text-slate">Οι ώρες μετάδοσης μοιράζονται στη διάρκεια της ημέρας. Αν θέλετε συγκεκριμένες ώρες ή θέση στο διάλειμμα, πατήστε «Θέλω αλλαγές».</p>
            @endif
        @endunless
    @endif

    {{-- ================= Δική σας επιλογή ================= --}}
    @if ($mode === 'manual')
        @if ($editing)
            <div class="mt-6 flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-onair/40 bg-onair/5 px-5 py-4">
                <p>Αλλάζετε την προσφορά <strong class="freq">{{ \App\Models\Quote::where('ulid', $editing)->value('number') }}</strong>. Με την αποθήκευση κρατά τον αριθμό της.</p>
                <a href="{{ route('quotes.show', $editing) }}" class="font-semibold text-slate hover:text-ink">Ακύρωση</a>
            </div>
            @if ($lockedSpecs)
                <p class="mt-3 text-sm text-slate">Ό,τι είχε προσθέσει η ομάδα μας (π.χ. έκπτωση ή πακέτο) μένει στην προσφορά όπως ήταν.</p>
            @endif
        @endif
        @if ($canSee && $manual && $manual['lines'])
            <a href="#synolo" class="lg:hidden sticky top-16 z-30 -mx-4 mt-6 flex items-center justify-between gap-3 bg-night px-4 py-3 text-white shadow-lg">
                <span class="text-sm text-white/70">Σύνολο με ΦΠΑ</span>
                <span class="flex items-center gap-3"><span class="text-lg font-extrabold text-glow freq">{{ $m($manual['total']) }}</span><span class="text-sm font-semibold underline">Αποθήκευση ↓</span></span>
            </a>
        @endif
        <div class="mt-8 grid gap-6 lg:grid-cols-[1fr_360px] items-start">
            <div class="space-y-6">
                <section class="rounded-3xl border border-line bg-surface p-6">
                    <h2 class="text-xl font-bold">Διάρκεια</h2>
                    <div class="mt-4 grid gap-4 sm:grid-cols-3">
                        <div>
                            <label for="m-start" class="text-sm font-semibold">Έναρξη</label>
                            <input id="m-start" type="date" min="{{ today()->format('Y-m-d') }}" wire:model.live="startsOn" class="{{ $input }}">
                            @error('startsOn')<p class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="m-days" class="text-sm font-semibold">Ημέρες</label>
                            <input id="m-days" type="number" min="1" max="365" wire:model.live.debounce.400ms="days" class="{{ $input }}">
                            @error('days')<p class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="m-title" class="text-sm font-semibold">Όνομα καμπάνιας <span class="font-normal text-slate">(προαιρετικό)</span></label>
                            <input id="m-title" type="text" maxlength="120" wire:model.blur="title" placeholder="π.χ. Εκπτώσεις Νοεμβρίου" class="{{ $input }}">
                        </div>
                    </div>
                </section>

                <section class="rounded-3xl border border-line bg-surface p-6">
                    <h2 class="text-xl font-bold">Ραδιόφωνο</h2>
                    @if ($stations->isEmpty())
                        <p class="mt-2 text-slate">Οι τιμές των σταθμών ενημερώνονται. Για ραδιοφωνική καμπάνια καλέστε μας και θα σας ετοιμάσουμε προσφορά.</p>
                    @else
                        <p class="mt-1 text-slate">{{ $book->isMetro() ? 'Χρέωση ανά δευτερόλεπτο μετάδοσης, χωρίς ΦΠΑ.' : 'Τιμές ανά μετάδοση, χωρίς ΦΠΑ.' }}</p>
                        <ul class="mt-4 space-y-3">
                            @foreach ($stations as $id => $st)
                                @php($r = $radio[$id] ?? null)
                                @continue(! $r)
                                <li class="rounded-2xl border {{ $r['on'] ? 'border-onair' : 'border-line' }}">
                                    <label class="flex flex-wrap cursor-pointer items-center gap-x-4 gap-y-1 p-4">
                                        <input type="checkbox" wire:model.live="radio.{{ $id }}.on" class="h-5 w-5 accent-[var(--color-onair)]">
                                        <span class="h-3 w-3 shrink-0 rounded-full" style="background: {{ $st->color }}" aria-hidden="true"></span>
                                        <span class="flex-1"><span class="font-semibold">{{ $st->name }}</span> <span class="text-slate freq">{{ $st->frequency_label }}</span>
                                            @if ($st->genre)<span class="block text-sm text-slate">{{ $st->genre }}</span>@endif
                                        </span>
                                        @if ($canSee)<span class="text-sm text-slate freq max-sm:basis-full max-sm:pl-16">{{ $book->stationPriceLabel($st) }}</span>@endif
                                    </label>
                                    @if ($r['on'])
                                        <div class="grid gap-3 border-t border-line p-4 sm:grid-cols-2 lg:grid-cols-4">
                                            <div>
                                                <label class="text-sm font-semibold" for="r{{ $id }}-spots">Μεταδόσεις / ημέρα</label>
                                                <input id="r{{ $id }}-spots" type="number" min="0" max="24" wire:model.live.debounce.400ms="radio.{{ $id }}.spots" class="{{ $small }} mt-1">
                                                @error('radio.'.$id.'.spots')<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                                            </div>
                                            <div>
                                                <label class="text-sm font-semibold" for="r{{ $id }}-sec">Διάρκεια σποτ</label>
                                                <select id="r{{ $id }}-sec" wire:model.live="radio.{{ $id }}.seconds" class="{{ $small }} mt-1">
                                                    @foreach ([10, 15, 20, 25, 30, 40, 45, 60] as $s)<option value="{{ $s }}">{{ $s }}″</option>@endforeach
                                                </select>
                                            </div>
                                            <div>
                                                <label class="text-sm font-semibold" for="r{{ $id }}-pos">Θέση</label>
                                                <select id="r{{ $id }}-pos" wire:model.live="radio.{{ $id }}.position" class="{{ $small }} mt-1">
                                                    <option value="standard">Κανονική</option>
                                                    @foreach (['first' => ['Πάντα πρώτη', 'pct_first_position'], 'middle' => ['Ενδιάμεσο σποτ', 'pct_middle_spot'], 'sandwich' => ['Sandwich', 'pct_sandwich']] as $k => [$l, $f])
                                                        @if ((float) $st->{$f} > 0)<option value="{{ $k }}">{{ $l }} (+{{ rtrim(rtrim(number_format((float) $st->{$f}, 2, ',', ''), '0'), ',') }}%)</option>@endif
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="flex items-end">
                                                @if ((float) $st->pct_fixed_hour > 0)
                                                    <label class="flex items-center gap-2 pb-2 text-sm font-semibold"><input type="checkbox" wire:model.live="radio.{{ $id }}.fixed" class="h-4 w-4 accent-[var(--color-onair)]"> Συγκεκριμένες ώρες (+{{ rtrim(rtrim(number_format((float) $st->pct_fixed_hour, 2, ',', ''), '0'), ',') }}%)</label>
                                                @endif
                                            </div>
                                            @if ($book->mention($st, 'live') !== null)
                                                <div>
                                                    <label class="text-sm font-semibold" for="r{{ $id }}-ml">Αναφορές παραγωγού / ημέρα</label>
                                                    <input id="r{{ $id }}-ml" type="number" min="0" max="24" wire:model.live.debounce.400ms="radio.{{ $id }}.ml" class="{{ $small }} mt-1">
                                                </div>
                                            @endif
                                            @if ($book->mention($st, 'recorded') !== null)
                                                <div>
                                                    <label class="text-sm font-semibold" for="r{{ $id }}-mr">Έτοιμες εκφωνήσεις / ημέρα</label>
                                                    <input id="r{{ $id }}-mr" type="number" min="0" max="24" wire:model.live.debounce.400ms="radio.{{ $id }}.mr" class="{{ $small }} mt-1">
                                                </div>
                                            @endif
                                            @if ($r['position'] === 'sandwich')
                                                <p class="text-sm text-slate sm:col-span-2 lg:col-span-4">Sandwich: το σποτ σας ακούγεται στην αρχή και ξανά στο τέλος του ίδιου διαφημιστικού διαλείμματος.</p>
                                            @endif
                                        </div>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </section>

                @if ($slots->isNotEmpty() || $prices['article'] !== null)
                    <section class="rounded-3xl border border-line bg-surface p-6">
                        <h2 class="text-xl font-bold">thrakitoday.gr</h2>
                        <ul class="mt-4 space-y-3">
                            @foreach ($slots as $id => $slot)
                                <li class="rounded-2xl border {{ ($banners[$id]['on'] ?? false) ? 'border-onair' : 'border-line' }} p-4">
                                    <label class="flex cursor-pointer items-start gap-4">
                                        <input type="checkbox" wire:model.live="banners.{{ $id }}.on" class="mt-1 h-5 w-5 accent-[var(--color-onair)]">
                                        <span class="flex-1"><span class="font-semibold">{{ $slot->name }}</span> <span class="text-sm text-slate">{{ $slot->dimensions }}</span>
                                            @if ($slot->description)<span class="block text-sm text-slate">{{ $slot->description }}</span>@endif
                                        </span>
                                        @if ($canSee)<span class="text-sm text-slate freq">{{ $m($book->slotPrice($slot)) }} / ημέρα</span>@endif
                                    </label>
                                    @if ($banners[$id]['on'] ?? false)
                                        <div class="mt-3 ml-9 max-w-xs">
                                            <label class="text-sm font-semibold" for="b{{ $id }}-days">Ημέρες <span class="font-normal text-slate">(κενό = όλη η καμπάνια)</span></label>
                                            <input id="b{{ $id }}-days" type="number" min="{{ max(1, $slot->min_days) }}" max="365" wire:model.live.debounce.400ms="banners.{{ $id }}.days" placeholder="{{ $days }}" class="{{ $small }} mt-1">
                                        </div>
                                    @endif
                                </li>
                            @endforeach
                            @if ($prices['article'] !== null)
                                <li class="rounded-2xl border border-line p-4">
                                    <div class="flex flex-wrap items-center gap-4">
                                        <span class="flex-1"><span class="font-semibold">Χορηγούμενο άρθρο</span><span class="block text-sm text-slate">Δημοσίευση με το κείμενο και τις φωτογραφίες σας.</span></span>
                                        <label class="flex items-center gap-2 text-sm font-semibold">Πλήθος <input type="number" min="0" max="30" wire:model.live.debounce.400ms="articles" class="{{ $small }} w-20"></label>
                                    </div>
                                    @if ((int) $articles > 0 && $prices['writing'] !== null)
                                        <label class="mt-3 flex items-center gap-2 text-sm font-semibold"><input type="checkbox" wire:model.live="articleWriting" class="h-4 w-4 accent-[var(--color-onair)]"> Γράψτε το κείμενο εσείς @if($canSee)(+{{ $m($prices['writing']) }} ανά άρθρο)@endif</label>
                                    @endif
                                </li>
                            @endif
                        </ul>
                    </section>
                @endif

                @if ($screenList->isNotEmpty())
                    <section class="rounded-3xl border border-line bg-surface p-6">
                        <h2 class="text-xl font-bold">Πινακίδα LED</h2>
                        <ul class="mt-4 space-y-3">
                            @foreach ($screenList as $id => $screen)
                                <li class="rounded-2xl border {{ ($screens[$id]['on'] ?? false) ? 'border-onair' : 'border-line' }} p-4">
                                    <label class="flex cursor-pointer items-start gap-4">
                                        <input type="checkbox" wire:model.live="screens.{{ $id }}.on" class="mt-1 h-5 w-5 accent-[var(--color-onair)]">
                                        <span class="flex-1"><span class="font-semibold">{{ $screen->name }}</span>@if($screen->location) <span class="text-sm text-slate">· {{ $screen->location }}</span>@endif
                                            <span class="block text-sm text-slate">Προβολή {{ $screen->slot_seconds }}″@if($screen->loops_per_hour), {{ $screen->loops_per_hour }} φορές την ώρα@endif</span>
                                        </span>
                                        @if ($canSee)<span class="text-sm text-slate freq">{{ $m($book->screenPrice($screen)) }} / ημέρα</span>@endif
                                    </label>
                                    @if ($screens[$id]['on'] ?? false)
                                        <div class="mt-3 ml-9 max-w-xs">
                                            <label class="text-sm font-semibold" for="s{{ $id }}-days">Ημέρες <span class="font-normal text-slate">(κενό = όλη η καμπάνια)</span></label>
                                            <input id="s{{ $id }}-days" type="number" min="{{ max(1, $screen->min_days) }}" max="365" wire:model.live.debounce.400ms="screens.{{ $id }}.days" placeholder="{{ $days }}" class="{{ $small }} mt-1">
                                        </div>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    </section>
                @endif

                @if (array_filter($prices['creative'], fn ($v) => $v !== null))
                    <section class="rounded-3xl border border-line bg-surface p-6">
                        <h2 class="text-xl font-bold">Δεν έχετε έτοιμο υλικό;</h2>
                        <p class="mt-1 text-slate">Το φτιάχνει η ομάδα μας. Αλλιώς ανεβάζετε το δικό σας μετά την παραγγελία.</p>
                        <div class="mt-4 grid gap-3 sm:grid-cols-2">
                            @foreach (\App\Services\Quotes\QuoteCalculator::CREATIVE as $k => $l)
                                @continue($prices['creative'][$k] === null)
                                <label class="flex cursor-pointer items-center gap-3 rounded-2xl border {{ ($creative[$k] ?? false) ? 'border-onair' : 'border-line' }} p-4">
                                    <input type="checkbox" wire:model.live="creative.{{ $k }}" class="h-5 w-5 accent-[var(--color-onair)]">
                                    <span class="flex-1 font-semibold">{{ $l }}</span>
                                    @if ($canSee)<span class="text-sm text-slate freq">{{ $m($prices['creative'][$k]) }}</span>@endif
                                </label>
                            @endforeach
                        </div>
                    </section>
                @endif
            </div>

            <aside id="synolo" class="scroll-mt-20 lg:sticky lg:top-20 rounded-3xl bg-night p-6 text-white" aria-live="polite">
                <h2 class="text-xl font-bold">Η προσφορά σας</h2>
                @if (! $canSee)
                    <p class="mt-3 text-white/75">Διαλέξτε μέσα και ημέρες. Για να δείτε την τιμή και να την αποθηκεύσετε, χρειάζεται δωρεάν λογαριασμός επαγγελματία.</p>
                    <a href="{{ $registerUrl }}" class="mt-5 flex justify-center rounded-full bg-onair px-6 py-3.5 font-semibold text-white hover:bg-onair-dark">{{ auth()->check() ? 'Επιβεβαίωση email' : 'Δωρεάν εγγραφή' }}</a>
                @elseif (! $manual || ! $manual['lines'])
                    <p class="mt-3 text-white/75">Διαλέξτε τουλάχιστον ένα μέσο από αριστερά.</p>
                @else
                    <ul class="mt-4 divide-y divide-white/10 border-y border-white/10">
                        @foreach ($manual['lines'] as $line)
                            <li class="flex items-start justify-between gap-3 py-2.5 text-[15px]">
                                <span><span class="block font-semibold">{{ $line['label'] }}</span>@if($line['detail'])<span class="block text-sm text-white/60">{{ $line['detail'] }}</span>@endif</span>
                                <span class="shrink-0 freq">{{ $m($line['amount']) }}</span>
                            </li>
                        @endforeach
                    </ul>
                    <dl class="mt-4 space-y-1">
                        <div class="flex justify-between text-white/70"><dt>Χωρίς ΦΠΑ</dt><dd class="freq">{{ $m($manual['subtotal']) }}</dd></div>
                        <div class="flex justify-between text-white/70"><dt>ΦΠΑ</dt><dd class="freq">{{ $m($manual['vat']) }}</dd></div>
                        <div class="flex justify-between text-2xl font-extrabold"><dt>Σύνολο</dt><dd class="freq text-glow">{{ $m($manual['total']) }}</dd></div>
                    </dl>
                    @foreach ($manual['warnings'] as $w)<p class="mt-3 text-sm text-glow">{{ $w }}</p>@endforeach
                    @if ($canSave)
                        <button type="button" wire:click="saveManual" wire:loading.attr="disabled" class="mt-6 flex w-full justify-center rounded-full bg-onair px-6 py-3.5 font-semibold text-white hover:bg-onair-dark disabled:opacity-60">{{ $editing ? 'Αποθήκευση αλλαγών' : 'Αποθήκευση προσφοράς' }}</button>
                        <p class="mt-3 text-center text-sm text-white/60">Χωρίς δέσμευση · PDF · την προχωράτε όποτε θέλετε</p>
                    @else
                        <p class="mt-5 text-sm text-white/70">Ως προσωπικό βλέπετε την τιμή· την αποθήκευση την κάνουν οι πελάτες από τον λογαριασμό τους.</p>
                    @endif
                @endif
            </aside>
        </div>
    @endif

    {{-- ================= Έτοιμα πακέτα ================= --}}
    @if ($mode === 'packages')
        @if ($packages->isEmpty())
            <div class="mt-8 rounded-3xl border border-line bg-surface p-8">
                <p class="text-slate">Αυτή τη στιγμή δεν υπάρχουν διαθέσιμα πακέτα. Δοκιμάστε τον οδηγό ή φτιάξτε την καμπάνια σας.</p>
                <div class="mt-4 flex gap-4"><button type="button" wire:click="choose('wizard')" class="font-semibold text-onair hover:underline">Ο οδηγός</button><button type="button" wire:click="choose('manual')" class="font-semibold text-onair hover:underline">Δική μου επιλογή</button></div>
            </div>
        @else
            <ul class="mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($packages as $p)
                    <li class="flex flex-col rounded-3xl border-2 bg-surface p-6 {{ $highlightPackage === $p->id ? 'border-onair shadow-lg' : 'border-line' }}">
                        <h2 class="text-2xl font-extrabold">{{ $p->name }}</h2>
                        @if ($p->tagline)<p class="mt-1 text-slate">{{ $p->tagline }}</p>@endif
                        @if ($p->includes)
                            <ul class="mt-4 space-y-1.5 text-[15px]">
                                @foreach ($p->includes as $line)<li class="flex gap-2"><span class="text-onair" aria-hidden="true">✓</span>{{ is_array($line) ? ($line['line'] ?? '') : $line }}</li>@endforeach
                            </ul>
                        @endif
                        <div class="mt-auto pt-6">
                            @if ($canSee && $p->price !== null)
                                <p class="text-3xl font-extrabold freq">{{ \App\Models\Package::formatPrice($p->price) }} <span class="text-sm font-normal text-slate">+ ΦΠΑ</span></p>
                                @if ($canSave)
                                    <x-form.button type="button" class="mt-4 w-full" wire:click="savePackage({{ $p->id }})" wire:loading.attr="disabled">Αποθήκευση ως προσφορά</x-form.button>
                                @endif
                            @elseif (! $canSee)
                                <a href="{{ $registerUrl }}" class="flex justify-center rounded-full border border-line px-5 py-3 font-semibold hover:border-onair">Δείτε την τιμή</a>
                            @else
                                <p class="text-slate">Τιμή κατόπιν επικοινωνίας.</p>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    @endif
</div>
