<x-layouts.site :title="'Παραγγελία '.$order->number">
    @php
        $step = $order->step();
        $company = \App\Models\Setting::allValues();
    @endphp
    <section class="mx-auto max-w-5xl px-4 sm:px-6 py-12 sm:py-16">
        <a href="{{ route('orders.index') }}" class="font-semibold text-slate hover:text-ink">← Οι παραγγελίες μου</a>
        <div class="mt-3 flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="text-slate">Παραγγελία {{ $order->number }}</p>
                <h1 class="text-3xl sm:text-4xl font-extrabold tracking-tight">{{ $order->quote?->title }}</h1>
            </div>
            @include('orders._stage')
        </div>

        @if (session('status'))<p class="mt-6 rounded-xl border border-emerald-500/40 bg-emerald-500/10 px-4 py-3" role="status">{{ session('status') }}</p>@endif
        @if (session('error'))<p class="mt-6 rounded-xl border border-red-500/40 bg-red-500/10 px-4 py-3" role="alert">{{ session('error') }}</p>@endif

        {{-- Πρόοδος --}}
        @if ($order->isCancelled())
            <div class="mt-8 rounded-3xl border border-line bg-surface p-6 sm:p-8">
                <p class="text-xl font-bold">Η παραγγελία ακυρώθηκε</p>
                @if ($order->cancel_reason)<p class="mt-2 text-slate">{{ $order->cancel_reason }}</p>@endif
                <a href="{{ route('quote.builder') }}" class="mt-5 inline-flex rounded-full bg-onair px-6 py-3 font-semibold text-white hover:bg-onair-dark">Νέα προσφορά</a>
            </div>
        @else
            <ol class="mt-8 grid grid-cols-4 gap-x-2 gap-y-5 sm:grid-cols-7" aria-label="Πορεία παραγγελίας">
                @foreach (\App\Models\Order::STEPS as $i => $label)
                    @php($state = $i < $step || $order->stage === 'completed' ? 'done' : ($i === $step ? 'now' : 'next'))
                    <li class="text-center" @if($state === 'now') aria-current="step" @endif>
                        <span class="block h-1.5 rounded-full {{ $state === 'done' ? 'bg-emerald-500' : ($state === 'now' ? 'bg-onair' : 'bg-line') }}"></span>
                        <span class="mt-2 flex items-center justify-center gap-1 text-sm {{ $state === 'now' ? 'font-bold text-ink' : 'text-slate' }}">
                            @if ($state === 'done')<x-ui-icon name="check" class="h-3.5 w-3.5 text-emerald-600" />@endif
                            {{ $label }}
                        </span>
                    </li>
                @endforeach
            </ol>
        @endif

        <div class="mt-8 grid gap-6 lg:grid-cols-[1fr_320px]">
            <div class="space-y-6">
                {{-- Τι γίνεται τώρα --}}
                @unless ($order->isCancelled())
                    <div class="rounded-3xl border border-line bg-surface p-6 sm:p-8">
                        <p class="text-sm font-semibold uppercase tracking-wider text-onair">Τώρα</p>
                        <h2 class="mt-1 text-2xl font-extrabold">{{ $order->stageLabel(true) }}</h2>
                        <p class="mt-2 text-slate">{{ $order->stageInfo()['text'] }}</p>

                        @if (in_array($order->stage, ['scheduled', 'running', 'completed'], true) && $order->starts_on)
                            <p class="mt-4 inline-flex items-center gap-2 rounded-xl bg-paper px-4 py-2.5 font-semibold">
                                <x-ui-icon name="calendar" class="h-5 w-5 text-onair" />
                                {{ $order->starts_on->format('d/m/Y') }} @if($order->ends_on) έως {{ $order->ends_on->format('d/m/Y') }}@endif
                            </p>
                        @endif

                        @if ($order->stage === 'awaiting_payment' && ! $order->isPaid())
                            @if ($order->payment_method === 'bank')
                                <div class="mt-6 rounded-2xl bg-paper p-5" x-data="{ copied: null, copy(v, k) { navigator.clipboard?.writeText(v.replace(/\s/g, k === 'iban' ? '' : ' ')); this.copied = k; setTimeout(() => this.copied = null, 1800) } }">
                                    <dl class="grid gap-4 sm:grid-cols-[1fr_auto_1fr]">
                                        <div><dt class="text-sm text-slate">Ποσό</dt><dd class="text-2xl font-extrabold freq">{{ $order->money('balance') }}</dd></div>
                                        <div>
                                            <dt class="text-sm text-slate">Αιτιολογία κατάθεσης</dt>
                                            <dd class="text-lg font-bold freq whitespace-nowrap">{{ $order->number }}</dd>
                                            <dd><button type="button" @click="copy('{{ $order->number }}', 'ref')" class="text-sm font-semibold text-onair hover:underline" x-text="copied === 'ref' ? 'Αντιγράφηκε' : 'Αντιγραφή'"></button></dd>
                                        </div>
                                        @if ($order->payment_due_on)
                                            <div><dt class="text-sm text-slate">Έως</dt><dd class="text-lg font-bold {{ $order->payment_due_on->isPast() ? 'text-red-600' : '' }}">{{ $order->payment_due_on->format('d/m/Y') }}</dd></div>
                                        @endif
                                    </dl>
                                    <div class="mt-5 space-y-3">
                                        @forelse ($accounts as $i => $a)
                                            <div class="rounded-xl border border-line bg-surface p-4">
                                                <p class="font-semibold">{{ $a['bank'] }}</p>
                                                <p class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 freq">
                                                    <span class="select-all text-lg tracking-wide">{{ $a['iban'] }}</span>
                                                    <button type="button" @click="copy('{{ $a['iban'] }}', 'iban')" class="text-sm font-semibold text-onair hover:underline" x-text="copied === 'iban' ? 'Αντιγράφηκε' : 'Αντιγραφή IBAN'"></button>
                                                </p>
                                                <p class="text-sm text-slate">Δικαιούχος: {{ $a['beneficiary'] }}</p>
                                            </div>
                                        @empty
                                            <p class="text-slate">Θα σας καλέσουμε για τα στοιχεία του λογαριασμού μας@if($company['phone']), ή καλέστε μας στο <a href="tel:{{ preg_replace('/\s+/', '', $company['phone']) }}" class="font-semibold text-onair">{{ $company['phone'] }}</a>@endif.</p>
                                        @endforelse
                                    </div>
                                    <p class="mt-4 text-sm text-slate">Γράψτε την αιτιολογία ακριβώς όπως είναι, για να βρούμε γρήγορα την κατάθεσή σας.</p>
                                </div>

                                <form method="POST" action="{{ route('orders.deposit', $order) }}" enctype="multipart/form-data" class="mt-6 space-y-4 border-t border-line pt-6" x-data="{ name: '' }">
                                    @csrf
                                    <h3 class="text-lg font-bold">Κάνατε την κατάθεση;</h3>
                                    <p class="text-[15px] text-slate">Πείτε μας για να την ελέγξουμε αμέσως. Αν έχετε το αποδεικτικό της τράπεζας, ανεβάστε το: βοηθά στις διατραπεζικές μεταφορές που αργούν.</p>
                                    <div>
                                        <label for="note" class="block text-sm font-semibold">Σημείωση <span class="font-normal text-slate">(προαιρετικό)</span></label>
                                        <input id="note" name="note" maxlength="500" value="{{ old('note') }}" placeholder="π.χ. Από Πειραιώς, στις 30/10" class="mt-1.5 block w-full rounded-xl border border-line bg-surface px-4 py-3 text-[16px] outline-none focus:border-onair focus:ring-2 focus:ring-onair/25">
                                    </div>
                                    <div>
                                        <label class="inline-flex cursor-pointer items-center gap-2 rounded-full border border-line px-5 py-2.5 font-semibold hover:border-onair">
                                            <x-ui-icon name="upload" class="h-4 w-4" />
                                            <span x-text="name || 'Αποδεικτικό (PDF ή φωτογραφία)'"></span>
                                            <input type="file" name="receipt" accept=".pdf,image/jpeg,image/png,image/webp" class="sr-only" @change="name = $event.target.files[0]?.name || ''">
                                        </label>
                                        @error('receipt')<p class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                                    </div>
                                    <x-form.button>Έκανα την κατάθεση</x-form.button>
                                </form>
                            @elseif ($order->payment_method === 'office')
                                <div class="mt-6 rounded-2xl bg-paper p-5">
                                    <p>Εξοφλήστε στο γραφείο μας με μετρητά ή κάρτα. Ποσό: <strong class="freq">{{ $order->money('balance') }}</strong>@if($order->payment_due_on), έως {{ $order->payment_due_on->format('d/m/Y') }}@endif.</p>
                                    @if ($company['address'])<p class="mt-2 font-semibold">{{ $company['address'] }}</p>@endif
                                    @if ($company['phone'])<p class="mt-1"><a href="tel:{{ preg_replace('/\s+/', '', $company['phone']) }}" class="font-semibold text-onair">{{ $company['phone'] }}</a></p>@endif
                                </div>
                            @endif
                        @endif
                    </div>
                @endunless

                {{-- Υλικό καμπάνιας --}}
                @if ($order->acceptsFiles() || $files->isNotEmpty())
                    @include('orders._materials')
                @endif

                {{-- Ιστορικό --}}
                <div class="rounded-3xl border border-line bg-surface p-6 sm:p-8">
                    <h2 class="text-xl font-bold">Ιστορικό</h2>
                    <ol class="mt-5 space-y-5">
                        @foreach ($events as $e)
                            <li class="relative flex gap-4">
                                <span class="mt-1.5 h-2.5 w-2.5 shrink-0 rounded-full {{ $loop->first ? 'bg-onair ring-4 ring-onair/20' : 'bg-line' }}" aria-hidden="true"></span>
                                <div class="min-w-0">
                                    <p class="font-semibold">{{ $e->title }}</p>
                                    @if ($e->note)<p class="mt-1 whitespace-pre-line text-slate">{{ $e->note }}</p>@endif
                                    <p class="mt-1 text-sm text-slate">{{ $e->created_at?->locale('el')->isoFormat('D MMM YYYY, HH:mm') }}</p>
                                </div>
                            </li>
                        @endforeach
                    </ol>
                </div>
            </div>

            <aside class="space-y-4">
                <dl class="space-y-3 rounded-2xl border border-line bg-surface p-6 text-[15px]">
                    <div class="flex justify-between gap-3"><dt class="text-slate">Σύνολο με ΦΠΑ</dt><dd class="font-bold freq">{{ $order->money() }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-slate">Τρόπος πληρωμής</dt><dd class="text-right font-semibold">{{ $order->methodLabel() }}</dd></div>
                    @if ($order->isPaid())
                        <div class="flex justify-between gap-3"><dt class="text-slate">Εξόφληση</dt><dd class="font-semibold text-emerald-600">✓ {{ $order->paid_at->format('d/m/Y') }}</dd></div>
                    @elseif ((float) $order->paid_amount > 0)
                        <div class="flex justify-between gap-3"><dt class="text-slate">Πληρώθηκαν</dt><dd class="font-semibold freq">{{ $order->money('paid_amount') }}</dd></div>
                        <div class="flex justify-between gap-3"><dt class="text-slate">Υπόλοιπο</dt><dd class="font-bold freq">{{ $order->money('balance') }}</dd></div>
                    @endif
                    <div class="flex justify-between gap-3"><dt class="text-slate">Ημ. παραγγελίας</dt><dd>{{ $order->created_at->format('d/m/Y') }}</dd></div>
                </dl>

                <div class="space-y-3 rounded-2xl border border-line bg-surface p-6">
                    @if ($order->invoice_path)
                        <a href="{{ route('orders.invoice', $order) }}" class="flex justify-center rounded-full bg-onair px-6 py-3 font-semibold text-white hover:bg-onair-dark">Τιμολόγιο {{ $order->invoice_number }}</a>
                    @elseif ($order->invoice_number)
                        <p class="text-center text-[15px]">Τιμολόγιο <strong>{{ $order->invoice_number }}</strong></p>
                    @endif
                    @if ($order->quote && ! $order->quote->trashed())
                        <a href="{{ route('quotes.pdf', $order->quote) }}" class="flex justify-center rounded-full border border-line px-6 py-3 font-semibold hover:border-onair">PDF προσφοράς</a>
                        <a href="{{ route('quotes.show', $order->quote) }}" class="block text-center font-semibold text-slate hover:text-ink">Αναλυτικά τι περιλαμβάνει</a>
                    @endif
                </div>

                <div id="minima" class="rounded-2xl bg-night p-6 text-white">
                    <p class="font-bold">Ερώτηση για την παραγγελία;</p>
                    <form method="POST" action="{{ route('orders.message', $order) }}" class="mt-3 space-y-3" x-data="{ busy: false, text: '' }" @submit="busy = true">
                        @csrf
                        <label for="order-msg" class="sr-only">Το μήνυμά σας</label>
                        <textarea id="order-msg" name="message" rows="3" maxlength="2000" required x-model="text" placeholder="Γράψτε μας εδώ· απαντάμε στο email σας και φαίνεται στο ιστορικό."
                                  class="block w-full rounded-xl border border-white/15 bg-white/10 px-3.5 py-2.5 text-[16px] text-white placeholder:text-white/50 outline-none focus:border-onair focus:ring-2 focus:ring-onair/40"></textarea>
                        @error('message')<p class="text-sm text-red-300">{{ $message }}</p>@enderror
                        <button :disabled="busy || ! text.trim()" class="w-full rounded-full bg-onair px-4 py-2.5 font-semibold hover:bg-onair-dark disabled:opacity-50">Αποστολή μηνύματος</button>
                    </form>
                    <div class="mt-4 flex flex-wrap items-center gap-x-4 gap-y-2 border-t border-white/10 pt-4 text-sm">
                        <span class="text-white/60">ή</span>
                        <button type="button" x-data @click="$dispatch('open-callback')" class="font-semibold text-white underline decoration-white/30 underline-offset-4 hover:decoration-white">Να σας καλέσουμε</button>
                        @if ($company['phone'])<a href="tel:{{ preg_replace('/\s+/', '', $company['phone']) }}" class="font-semibold text-white/80 hover:text-white">{{ $company['phone'] }}</a>@endif
                    </div>
                </div>
            </aside>
        </div>
    </section>
</x-layouts.site>
