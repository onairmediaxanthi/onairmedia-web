@php($m = fn ($v) => \App\Services\Quotes\QuoteCalculator::money($v))
<x-layouts.site :title="$quote->number.' — Οι προσφορές μου'">
    <section class="mx-auto max-w-5xl px-4 sm:px-6 py-12 sm:py-16">
        <a href="{{ route('quotes.index') }}" class="font-semibold text-slate hover:text-ink">← Οι προσφορές μου</a>
        <div class="mt-3 flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="text-slate freq">{{ $quote->number }} · {{ $quote->created_at->format('d/m/Y') }}</p>
                <h1 class="mt-1 text-3xl sm:text-4xl font-extrabold tracking-tight">{{ $quote->title }}</h1>
            </div>
            @include('quotes._status')
        </div>

        @if (session('status'))<p class="mt-6 rounded-xl border border-emerald-500/40 bg-emerald-500/10 px-4 py-3" role="status">{{ session('status') }}</p>@endif
        @if (session('error'))<p class="mt-6 rounded-xl border border-red-500/40 bg-red-500/10 px-4 py-3" role="alert">{{ session('error') }}</p>@endif

        <div class="mt-8 grid gap-6 lg:grid-cols-[1fr_320px] items-start">
            <div class="rounded-2xl border border-line bg-surface p-6">
                @foreach ($quote->groupedItems() as $group => $lines)
                    <h2 class="mt-6 first:mt-0 text-sm font-bold uppercase tracking-wide text-onair">{{ $group }}</h2>
                    <ul class="mt-2 divide-y divide-line">
                        @foreach ($lines as $line)
                            <li class="flex items-start justify-between gap-4 py-3">
                                <span>
                                    <span class="block font-semibold">{{ $line['label'] }}</span>
                                    @if ($line['detail'])<span class="block text-sm text-slate">{{ $line['detail'] }}</span>@endif
                                    @foreach ($line['includes'] ?? [] as $inc)<span class="block text-sm text-slate">· {{ $inc }}</span>@endforeach
                                    <span class="block text-sm text-slate freq">{{ $line['quantity'] }} × {{ $m($line['unit_price']) }}</span>
                                </span>
                                <span class="shrink-0 font-semibold freq">{{ $m($line['amount']) }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endforeach
                <dl class="mt-6 space-y-1 border-t border-line pt-4">
                    <div class="flex justify-between text-slate"><dt>Σύνολο χωρίς ΦΠΑ</dt><dd class="freq">{{ $quote->money('subtotal') }}</dd></div>
                    <div class="flex justify-between text-slate"><dt>ΦΠΑ {{ rtrim(rtrim((string) $quote->vat_rate, '0'), '.') }}%</dt><dd class="freq">{{ $quote->money('vat') }}</dd></div>
                    <div class="flex justify-between text-2xl font-extrabold"><dt>Τελικό ποσό</dt><dd class="freq">{{ $quote->money('total') }}</dd></div>
                </dl>
            </div>

            <aside class="space-y-4">
                <dl class="rounded-2xl border border-line bg-surface p-6 space-y-3 text-[15px]">
                    @if ($quote->starts_on)<div><dt class="text-sm text-slate">Έναρξη</dt><dd class="font-semibold">{{ $quote->starts_on->format('d/m/Y') }}</dd></div>@endif
                    <div><dt class="text-sm text-slate">Διάρκεια</dt><dd class="font-semibold">{{ $quote->days }} {{ $quote->days === 1 ? 'ημέρα' : 'ημέρες' }}</dd></div>
                    <div><dt class="text-sm text-slate">Οι τιμές ισχύουν έως</dt><dd class="font-semibold">{{ $quote->valid_until->format('d/m/Y') }}</dd></div>
                    @if ($quote->submitted_at)<div><dt class="text-sm text-slate">Στάλθηκε</dt><dd class="font-semibold">{{ $quote->submitted_at->format('d/m/Y H:i') }}</dd></div>@endif
                </dl>

                <div class="rounded-2xl border border-line bg-surface p-6 space-y-3">
                    @if ($quote->isEditable())
                        <a href="{{ route('quotes.checkout', $quote) }}" class="flex justify-center rounded-full bg-onair px-6 py-3.5 font-semibold text-white hover:bg-onair-dark">Προχώρηση στο ταμείο</a>
                    @elseif ($quote->isExpired())
                        <form method="POST" action="{{ route('quotes.renew', $quote) }}">@csrf
                            <button class="flex w-full justify-center rounded-full bg-onair px-6 py-3.5 font-semibold text-white hover:bg-onair-dark">Ανανέωση με τρέχουσες τιμές</button>
                        </form>
                    @endif
                    <a href="{{ route('quotes.pdf', $quote) }}" class="flex justify-center rounded-full border border-line px-6 py-3 font-semibold hover:border-onair">Λήψη PDF</a>
                    @if ($quote->status !== 'ordered')
                        <form method="POST" action="{{ route('quotes.destroy', $quote) }}" x-data @submit="if (! window.confirm('Να διαγραφεί η προσφορά {{ $quote->number }};')) $event.preventDefault()">
                            @csrf @method('DELETE')
                            <button class="w-full py-2 font-semibold text-slate hover:text-red-600">Διαγραφή προσφοράς</button>
                        </form>
                    @endif
                </div>
                @if ($quote->status === 'submitted')
                    <p class="rounded-2xl bg-amber-500/10 p-5 text-[15px]">Η ομάδα μας θα επικοινωνήσει μαζί σας για επιβεβαίωση του προγράμματος, του υλικού και της πληρωμής.</p>
                @endif
            </aside>
        </div>
    </section>
</x-layouts.site>
