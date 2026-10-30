{{-- Υλικό καμπάνιας: ένα κουτί ανά μέσο, με οδηγίες, έλεγχο αρχείων και κείμενα --}}
@php
    $byslot = $files->groupBy(fn ($f) => $f->slot ?? '_');
    $staffFiles = $files->where('from', 'staff');
    $editable = $order->acceptsFiles();
    $canRemove = in_array($order->stage, ['received', 'awaiting_payment', 'payment_review', 'materials'], true);
    $required = collect($plan)->filter(fn ($s) => $s['required'])->count();
    $ready = collect($slotStatus)->filter(fn ($s, $k) => $plan[$k]['required'] && $s['state'] === 'done')->count();
    $slotError = session('slot_error');
@endphp
<div id="yliko" class="rounded-3xl border p-6 sm:p-8 {{ $order->stage === 'materials' ? 'border-onair bg-onair/5' : 'border-line bg-surface' }}">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <h2 class="text-xl font-bold">Υλικό καμπάνιας</h2>
        @if ($order->files_purged_at)
            <span class="rounded-full bg-slate/15 px-3 py-1 text-xs font-bold text-slate">Τα αρχεία αρχειοθετήθηκαν</span>
        @elseif ($required)
            <span class="rounded-full px-3 py-1 text-xs font-bold {{ $ready === $required ? 'bg-emerald-500/15 text-emerald-700 dark:text-emerald-300' : 'bg-onair text-white' }}">{{ $ready }} / {{ $required }} έτοιμα</span>
        @endif
    </div>
    @if ($order->files_purged_at)
        <p class="mt-2 text-[15px] text-slate">Μετά την ολοκλήρωση της καμπάνιας, τα αρχεία αρχειοθετήθηκαν από την ομάδα μας και αφαιρέθηκαν από το Studio στις {{ $order->files_purged_at->format('d/m/Y') }}. Αν τα χρειαστείτε, ζητήστε τα από εμάς.</p>
    @elseif ($order->stage === 'materials' && ($ask = $events->firstWhere('stage', 'materials')) && $ask->note)
        <div class="mt-4 rounded-2xl bg-surface p-4">
            <p class="text-sm font-semibold text-slate">Μήνυμα από την ομάδα μας</p>
            <p class="mt-1 whitespace-pre-line">{{ $ask->note }}</p>
        </div>
    @elseif ($editable)
        <p class="mt-2 text-[15px] text-slate">Για κάθε μέσο της καμπάνιας σας, εδώ θα βρείτε τι ακριβώς χρειαζόμαστε. Μπορείτε να τα ανεβάσετε από τώρα.</p>
    @endif

    @if ($staffFiles->isNotEmpty())
        <div class="mt-5 rounded-2xl border border-blue-500/30 bg-blue-500/5 p-4">
            <p class="font-semibold">Από την ομάδα μας, για έγκριση</p>
            <ul class="mt-2 space-y-2">
                @foreach ($staffFiles as $f)
                    <li class="flex items-center justify-between gap-3">
                        @if ($f->isPurged())<span class="text-slate">{{ $f->name }}</span>
                        @else<a href="{{ route('orders.file', [$order, $f]) }}" class="truncate font-semibold hover:text-onair">{{ $f->name }}</a>@endif
                        <span class="shrink-0 text-sm text-slate">{{ $f->created_at->format('d/m/Y') }}</span>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="mt-5 space-y-4">
        @foreach ($plan as $key => $slot)
            @php
                $st = $slotStatus[$key];
                $fs = $byslot->get($key, collect())->where('from', 'customer');
                $anchor = 'slot-'.str_replace(':', '-', $key);
                $err = $slotError && $slotError[0] === $key ? $slotError[1] : null;
            @endphp
            <section id="{{ $anchor }}" class="rounded-2xl border bg-surface p-5 {{ $err ? 'border-red-500' : ($st['state'] === 'done' ? 'border-emerald-500/40' : 'border-line') }}"
                     x-data="{ open: {{ $st['state'] === 'missing' && $editable ? 'true' : 'false' }} }">
                <button type="button" class="flex w-full items-start justify-between gap-3 text-left" @click="open = ! open" :aria-expanded="open">
                    <span class="flex min-w-0 items-start gap-3">
                        <span class="mt-0.5 grid h-10 w-10 shrink-0 place-items-center rounded-xl {{ $st['state'] === 'done' ? 'bg-emerald-500/15 text-emerald-600' : 'bg-onair/10 text-onair' }}">
                            @if ($st['state'] === 'done')<x-ui-icon name="check" class="h-5 w-5" />@else<x-ui-icon :name="$slot['icon']" class="h-5 w-5" />@endif
                        </span>
                        <span class="min-w-0">
                            <span class="block font-bold">{{ $slot['title'] }}</span>
                            <span class="block text-sm text-slate">{{ $slot['subtitle'] }}</span>
                            @if ($st['state'] === 'missing' && ! $order->files_purged_at)
                                <span class="mt-1 block text-sm font-semibold text-onair">Λείπει: {{ implode(', ', $st['missing']) }}</span>
                            @elseif ($st['state'] === 'done')
                                <span class="mt-1 block text-sm font-semibold text-emerald-600">Έτοιμο</span>
                            @endif
                        </span>
                    </span>
                    <span class="mt-2 shrink-0 text-slate transition" :class="open && 'rotate-90'" aria-hidden="true"><x-ui-icon name="arrow" class="h-4 w-4" /></span>
                </button>

                <div x-show="open" x-cloak class="mt-4 space-y-4 border-t border-line pt-4">
                    @if ($slot['we_make'])
                        <p class="rounded-xl bg-onair/5 px-4 py-2.5 text-sm font-semibold text-onair">Αυτό το φτιάχνουμε εμείς για εσάς</p>
                    @endif
                    <ul class="space-y-1.5 text-[15px]">
                        @foreach ($slot['help'] as $h)
                            <li class="flex gap-2"><span class="mt-2 h-1.5 w-1.5 shrink-0 rounded-full bg-onair" aria-hidden="true"></span><span>{{ $h }}</span></li>
                        @endforeach
                    </ul>
                    <p class="text-sm text-slate">Δεκτά αρχεία: {{ strtoupper(implode(', ', array_unique($slot['accept']))) }} · έως {{ $slot['max_mb'] }} MB το καθένα</p>

                    @if ($err)<p class="rounded-xl border border-red-500/40 bg-red-500/10 px-4 py-3 text-[15px]" role="alert">{{ $err }}</p>@endif

                    @if ($fs->isNotEmpty())
                        <ul class="divide-y divide-line rounded-xl border border-line">
                            @foreach ($fs as $f)
                                <li class="flex items-start justify-between gap-3 px-4 py-3">
                                    <span class="min-w-0">
                                        @if ($f->isPurged())<span class="block truncate font-semibold text-slate">{{ $f->name }}</span>
                                        @else<a href="{{ route('orders.file', [$order, $f]) }}" class="block truncate font-semibold hover:text-onair">{{ $f->name }}</a>@endif
                                        <span class="block text-sm text-slate">{{ $f->techLabel() }}</span>
                                        @foreach ($f->warnings() as $w)<span class="mt-0.5 block text-sm text-amber-700 dark:text-amber-300">{{ $w }}</span>@endforeach
                                    </span>
                                    @if ($canRemove && ! $f->isPurged())
                                        <form method="POST" action="{{ route('orders.file-remove', [$order, $f]) }}" x-data @submit="if (! window.confirm('Να αφαιρεθεί το {{ addslashes($f->name) }};')) $event.preventDefault()">
                                            @csrf @method('DELETE')
                                            <button class="text-sm font-semibold text-slate hover:text-red-600">Αφαίρεση</button>
                                        </form>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    @endif

                    @if ($editable && ! $order->files_purged_at)
                        @if ($slot['text'])
                            <form method="POST" action="{{ route('orders.brief', $order) }}" class="space-y-2">
                                @csrf
                                <input type="hidden" name="slot" value="{{ $key }}">
                                <label for="brief-{{ $anchor }}" class="block text-sm font-semibold">{{ $slot['text'] }}@unless ($slot['text_required']) <span class="font-normal text-slate">(προαιρετικό)</span>@endunless</label>
                                <textarea id="brief-{{ $anchor }}" name="text" rows="{{ $slot['text_required'] ? 4 : 2 }}" maxlength="3000" placeholder="{{ $slot['text_hint'] }}"
                                          class="block w-full rounded-xl border border-line bg-surface px-4 py-3 text-[16px] outline-none focus:border-onair focus:ring-2 focus:ring-onair/25">{{ $order->brief[$key] ?? '' }}</textarea>
                                <button class="rounded-full border border-line px-5 py-2 text-sm font-semibold hover:border-onair">Αποθήκευση κειμένου</button>
                            </form>
                        @endif

                        <form method="POST" action="{{ route('orders.upload', $order) }}" enctype="multipart/form-data" class="space-y-3"
                              x-data="{ names: [], busy: false, tooBig: false,
                                  pick(list) { this.names = [...list].map(f => f.name); this.tooBig = [...list].reduce((s, f) => s + f.size, 0) > {{ \App\Models\Order::BATCH_MAX_MB }} * 1048576 || [...list].some(f => f.size > {{ $slot['max_mb'] }} * 1048576) } }"
                              @submit="if (tooBig) { $event.preventDefault(); return; } busy = true">
                            @csrf
                            <input type="hidden" name="slot" value="{{ $key }}">
                            <label class="flex cursor-pointer flex-col items-center justify-center gap-1.5 rounded-2xl border-2 border-dashed border-line px-4 py-6 text-center hover:border-onair"
                                   @dragover.prevent @drop.prevent="$refs.files.files = $event.dataTransfer.files; pick($event.dataTransfer.files)">
                                <x-ui-icon name="upload" class="h-6 w-6 text-onair" />
                                <span class="font-semibold" x-text="names.length ? names.join(', ') : '{{ $slot['we_make'] ? 'Λογότυπο, φωτογραφίες ή αρχεία (προαιρετικό)' : 'Διαλέξτε ή σύρετε εδώ το αρχείο' }}'"></span>
                                <input x-ref="files" type="file" name="files[]" multiple class="sr-only" accept="{{ collect($slot['accept'])->unique()->map(fn ($e) => '.'.$e)->implode(',') }}" @change="pick($event.target.files)">
                            </label>
                            <p x-show="tooBig" x-cloak class="text-sm text-red-600 dark:text-red-400">Πολύ μεγάλο αρχείο για εδώ (έως {{ $slot['max_mb'] }} MB). Για μεγαλύτερα, γράψτε μας σύνδεσμο WeTransfer / Google Drive στο κείμενο.</p>
                            <button :disabled="busy || tooBig || ! names.length" class="rounded-full bg-onair px-5 py-2.5 text-sm font-semibold text-white hover:bg-onair-dark disabled:opacity-50">
                                <span x-show="! busy">Ανέβασμα</span><span x-show="busy" x-cloak>Ανεβαίνει και ελέγχεται…</span>
                            </button>
                        </form>
                    @endif
                </div>
            </section>
        @endforeach
    </div>

    @if ($order->stage === 'materials')
        <form method="POST" action="{{ route('orders.materials-done', $order) }}" class="mt-6 border-t border-line pt-5 space-y-3">
            @csrf
            @if ($ready < $required)
                <p class="text-[15px] text-slate">Όταν είναι όλα έτοιμα, πατήστε το κουμπί. Αν κάτι θα μας το στείλετε αλλιώς (email, USB), γράψτε μας πώς:</p>
                <input name="note" maxlength="1000" placeholder="π.χ. Το σποτ θα σας το φέρει το studio μας την Τρίτη" class="block w-full rounded-xl border border-line bg-surface px-4 py-3 text-[16px] outline-none focus:border-onair focus:ring-2 focus:ring-onair/25">
            @else
                <p class="text-[15px] font-semibold text-emerald-600">Όλα έτοιμα! Πατήστε για να ξεκινήσουμε την προετοιμασία.</p>
            @endif
            <x-form.button>Έστειλα όλο το υλικό</x-form.button>
        </form>
    @endif
</div>
