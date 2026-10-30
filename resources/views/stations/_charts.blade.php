{{-- Top 40 εβδομάδας / μήνα και «Μόλις έπαιξαν» --}}
@php
    $mv = fn ($c) => match ($c->movement()) {
        'new' => ['ΝΕΟ', 'bg-onair text-white'],
        'up' => ['▲ '.($c->prev_position - $c->position), 'bg-emerald-500/15 text-emerald-700 dark:text-emerald-300'],
        'down' => ['▼ '.($c->position - $c->prev_position), 'bg-red-500/10 text-red-600 dark:text-red-300'],
        default => ['=', 'bg-slate/10 text-slate'],
    };
    $first = array_key_first($charts);
    $accent = $station->color ?: '#c80c92';
@endphp
@if ($charts)
    <section class="mt-12" aria-labelledby="top40-title" x-data="{ tab: '{{ $first }}' }">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <p class="text-sm font-bold uppercase tracking-[.18em]" style="color: {{ $accent }}">Top {{ \App\Services\Airplay\ChartBuilder::size() }}</p>
                <h2 id="top40-title" class="text-2xl font-bold">Τα πιο αγαπημένα</h2>
            </div>
            @if (count($charts) > 1)
                <div class="inline-flex rounded-full border border-line bg-surface p-1" role="tablist">
                    @foreach ($charts as $period => $c)
                        <button type="button" role="tab" @click="tab = '{{ $period }}'" :aria-selected="tab === '{{ $period }}'"
                                class="rounded-full px-4 py-1.5 text-sm font-semibold" :class="tab === '{{ $period }}' ? 'text-white' : 'text-slate hover:text-ink'"
                                :style="tab === '{{ $period }}' ? 'background: {{ $accent }}' : ''">{{ $period === 'week' ? 'Της εβδομάδας' : 'Του μήνα' }}</button>
                    @endforeach
                </div>
            @endif
        </div>

        @foreach ($charts as $period => $c)
            <div x-show="tab === '{{ $period }}'" @if($period !== $first) x-cloak @endif>
                <p class="mt-2 text-sm text-slate">{{ \App\Models\Chart::periodLabel($period, $c['start']) }}</p>
                <ol class="mt-4 divide-y divide-line overflow-hidden rounded-2xl border border-line bg-surface">
                    @foreach ($c['rows']->take(10) as $row)
                        @include('stations._chart-row', ['c' => $row, 'm' => $mv($row), 'big' => $row->position <= 3, 'unit' => $period])
                    @endforeach
                </ol>
                @if ($c['rows']->count() > 10)
                    <details class="group mt-3">
                        <summary class="cursor-pointer list-none rounded-full border border-line px-5 py-2.5 text-center font-semibold hover:border-onair [&::-webkit-details-marker]:hidden">
                            <span class="group-open:hidden">Όλο το Top {{ $c['rows']->count() }}</span><span class="hidden group-open:inline">Λιγότερα</span>
                        </summary>
                        <ol class="mt-3 divide-y divide-line overflow-hidden rounded-2xl border border-line bg-surface">
                            @foreach ($c['rows']->slice(10) as $row)
                                @include('stations._chart-row', ['c' => $row, 'm' => $mv($row), 'big' => false, 'unit' => $period])
                            @endforeach
                        </ol>
                    </details>
                @endif
            </div>
        @endforeach
        <p class="mt-3 text-sm text-slate">Από τις μεταδόσεις του σταθμού. Η λίστα της εβδομάδας βγαίνει κάθε Δευτέρα, του μήνα την 1η κάθε μήνα.</p>
    </section>
@endif

@if ($recent->isNotEmpty())
    <section class="mt-12" aria-labelledby="recent-title">
        <h2 id="recent-title" class="text-2xl font-bold">Μόλις έπαιξαν</h2>
        <ul class="mt-4 space-y-2">
            @foreach ($recent as $a)
                <li class="flex items-center gap-4 rounded-xl border border-line bg-surface px-4 py-3">
                    <span class="w-12 shrink-0 text-sm font-semibold text-slate freq">{{ $a->played_at->format('H:i') }}</span>
                    <span class="min-w-0">
                        <span class="block truncate font-semibold">{{ $a->song->title }}</span>
                        @if ($a->song->artist)<span class="block truncate text-sm text-slate">{{ $a->song->artist }}</span>@endif
                    </span>
                </li>
            @endforeach
        </ul>
    </section>
@endif
