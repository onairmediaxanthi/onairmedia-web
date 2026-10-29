@php
    $title = $station->seo_title ?: "{$station->name} — {$station->slogan} | On Air Media";
    $schema = [
        '@context' => 'https://schema.org',
        '@type' => 'RadioStation',
        'name' => $station->name,
        'slogan' => $station->slogan,
        'url' => route('stations.show', $station),
        'logo' => $station->logo_url,
        'areaServed' => $station->coverage,
        'broadcastFrequency' => $station->frequency_label,
        'parentOrganization' => ['@type' => 'Organization', 'name' => 'On Air Media Group'],
    ];
    $desc = $station->seo_description ?: "Ακούστε ζωντανά τον {$station->name} ({$station->frequency_label}) στην Ξάνθη. {$station->slogan}";
@endphp

<x-layouts.site :title="$title" :description="\Illuminate\Support\Str::limit($desc, 155)" :og-image="$station->cover_url ?? $station->logo_url">
    @push('head')
        <script type="application/ld+json">{!! json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}</script>
    @endpush

    <section class="relative isolate overflow-hidden bg-night text-white">
        @if ($station->cover_url)
            <img src="{{ $station->cover_url }}" alt="" class="absolute inset-0 -z-10 h-full w-full object-cover opacity-35">
        @endif
        <div class="absolute inset-0 -z-10" style="background: radial-gradient(80% 120% at 85% 0%, {{ $station->color }}66, transparent 60%)" aria-hidden="true"></div>

        <div class="mx-auto max-w-7xl px-4 sm:px-6 py-14 sm:py-20 grid gap-10 md:grid-cols-[auto_1fr] items-center">
            <div class="h-36 w-36 sm:h-44 sm:w-44 rounded-3xl bg-white grid place-items-center shadow-xl ring-4" style="--tw-ring-color: {{ $station->color }}">
                @if ($station->logo_url)<img src="{{ $station->logo_url }}" alt="Λογότυπο {{ $station->name }}" class="max-h-32 max-w-32 object-contain">@endif
            </div>
            <div x-data>
                <p class="freq text-glow font-semibold text-lg">{{ $station->frequency_label }}@if($station->coverage) · {{ $station->coverage }}@endif</p>
                <h1 class="mt-1 text-4xl sm:text-6xl font-extrabold tracking-tight leading-[1.02]">{{ $station->name }}</h1>
                @if ($station->slogan)<p class="mt-4 text-xl sm:text-2xl text-white/85">«{{ $station->slogan }}»</p>@endif

                <div class="mt-8 flex flex-wrap items-center gap-4">
                    <button type="button" @click="$store.radio.toggle('{{ $station->slug }}')"
                            class="inline-flex items-center gap-3 rounded-full px-7 py-3.5 font-semibold text-white"
                            style="background: {{ $station->color }}">
                        <svg x-show="!$store.radio.isPlaying('{{ $station->slug }}')" class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M8 5v14l11-7z"/></svg>
                        <svg x-show="$store.radio.isPlaying('{{ $station->slug }}')" x-cloak class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M6 6h12v12H6z"/></svg>
                        <span x-text="$store.radio.isPlaying('{{ $station->slug }}') ? 'Σταματήστε' : 'Ακούστε ζωντανά'">Ακούστε ζωντανά</span>
                    </button>
                    <p class="text-white/80 min-w-0">
                        <span class="block text-sm text-white/60">Παίζει τώρα</span>
                        <span class="block font-medium" x-text="$store.radio.nowLine('{{ $station->slug }}') || '—'">—</span>
                    </p>
                </div>
            </div>
        </div>
    </section>

    <div class="mx-auto max-w-7xl px-4 sm:px-6 py-16 grid gap-12 lg:grid-cols-[1fr_340px]">
        <article>
            <h2 class="text-2xl font-bold">Ο σταθμός</h2>
            @if ($description)
                <div class="mt-4 max-w-prose text-[17px] leading-relaxed text-ink/90 [&_a]:text-onair [&_a]:underline [&_h2]:mt-8 [&_h2]:text-xl [&_h2]:font-bold [&_h3]:mt-6 [&_h3]:font-bold [&_p]:mt-4 [&_ul]:mt-4 [&_ul]:list-disc [&_ul]:pl-6 [&_ol]:mt-4 [&_ol]:list-decimal [&_ol]:pl-6">
                    {!! $description !!}
                </div>
            @else
                <p class="mt-4 text-slate max-w-prose">{{ $station->name }} στους {{ $station->frequency_label }}: {{ mb_strtolower($station->genre ?? 'μουσική') }} για όλη την Ξάνθη, όλο το εικοσιτετράωρο.</p>
            @endif

            @if ($audience)
                <section class="mt-12" aria-labelledby="koino-title">
                    <h2 id="koino-title" class="text-2xl font-bold">Ποιοι ακούνε</h2>
                    <dl class="mt-4 space-y-4">
                        @foreach ($audience as $group)
                            <div>
                                <dt class="text-sm text-slate">{{ $group['label'] }}</dt>
                                <dd class="mt-2 flex flex-wrap gap-2">
                                    @foreach ($group['items'] as $item)
                                        <span class="rounded-full border border-line bg-surface px-3 py-1 text-sm font-medium">{{ $item }}</span>
                                    @endforeach
                                </dd>
                            </div>
                        @endforeach
                    </dl>
                </section>
            @endif

            <div id="diafimisi-stathmou" class="mt-12 rounded-2xl border border-line bg-surface p-7">
                <h2 class="text-2xl font-bold">Διαφημιστείτε στον {{ $station->name }}</h2>
                <p class="mt-2 text-slate max-w-prose">Διαλέξτε ζώνη ωρών και διάρκεια, ή συνδυάστε τον σταθμό με τους υπόλοιπους.</p>
                @if ($canSeePrices && $book->stationBase($station) !== null)
                    <dl class="mt-5 grid gap-3 sm:grid-cols-3">
                        @if ($book->isMetro())
                            <div class="rounded-xl bg-paper p-4"><dt class="text-sm text-slate">Μετάδοση, ανά δευτερόλεπτο</dt><dd class="mt-1 text-xl font-bold">{{ number_format((float) $station->metro_price_second, 4, ',', '.') }} €</dd><dd class="text-sm text-slate">σποτ 30″: {{ \App\Services\Quotes\QuoteCalculator::money($book->spotUnit($station, 30)) }}</dd></div>
                        @else
                            <div class="rounded-xl bg-paper p-4"><dt class="text-sm text-slate">Μετάδοση σποτ έως {{ $station->spot_seconds }}″</dt><dd class="mt-1 text-xl font-bold">{{ number_format((float) $station->price_spot, 2, ',', '.') }} €</dd></div>
                        @endif
                        @if (($v = $book->mention($station, 'live')) !== null)<div class="rounded-xl bg-paper p-4"><dt class="text-sm text-slate">Αναφορά από τον παραγωγό</dt><dd class="mt-1 text-xl font-bold">{{ number_format($v, 2, ',', '.') }} €</dd></div>@endif
                        @if (($v = $book->mention($station, 'recorded')) !== null)<div class="rounded-xl bg-paper p-4"><dt class="text-sm text-slate">Έτοιμη εκφώνηση</dt><dd class="mt-1 text-xl font-bold">{{ number_format($v, 2, ',', '.') }} €</dd></div>@endif
                    </dl>
                    <p class="mt-3 text-sm text-slate">{{ $book->isMetro() ? 'Χρέωση ανά δευτερόλεπτο μετάδοσης' : 'Τιμές ανά μετάδοση' }}, χωρίς ΦΠΑ 24%.@if((float) $station->pct_first_position > 0) Πάντα πρώτη θέση: +{{ rtrim(rtrim(number_format((float) $station->pct_first_position, 2, ',', ''), '0'), ',') }}%.@endif @if((float) $station->pct_sandwich > 0) Sandwich: +{{ rtrim(rtrim(number_format((float) $station->pct_sandwich, 2, ',', ''), '0'), ',') }}%.@endif</p>
                @elseif (! $canSeePrices)
                    <p class="mt-4"><a href="{{ route('register') }}" class="font-semibold text-onair hover:underline">Δωρεάν εγγραφή για να δείτε τιμές</a></p>
                @endif
                <a href="{{ route('quote.builder', ['tropos' => 'manual', 'stathmos' => $station->slug]) }}" class="mt-5 inline-flex rounded-full bg-onair px-6 py-3 font-semibold text-white hover:bg-onair-dark">Φτιάξτε προσφορά με τον {{ $station->name }}</a>
            </div>
        </article>

        <aside aria-labelledby="facts-title">
            <h2 id="facts-title" class="sr-only">Στοιχεία</h2>
            <dl class="rounded-2xl border border-line bg-surface divide-y divide-line">
                <div class="p-5"><dt class="text-sm text-slate">Συχνότητα</dt><dd class="mt-1 font-semibold freq">{{ $station->frequency_label }}</dd></div>
                @if ($station->genre)<div class="p-5"><dt class="text-sm text-slate">Μουσική</dt><dd class="mt-1 font-semibold">{{ $station->genre }}</dd></div>@endif
                @if ($station->coverage)<div class="p-5"><dt class="text-sm text-slate">Περιοχή</dt><dd class="mt-1 font-semibold">{{ $station->coverage }}</dd></div>@endif
                @if ($station->website)<div class="p-5"><dt class="text-sm text-slate">Ιστοσελίδα</dt><dd class="mt-1"><a href="{{ $station->website }}" target="_blank" rel="noopener" class="font-semibold text-onair hover:underline break-all">{{ preg_replace('#^https?://(www\.)?#', '', rtrim($station->website, '/')) }}</a></dd></div>@endif
            </dl>

            <h2 class="mt-10 font-bold">Άλλοι σταθμοί</h2>
            <ul class="mt-3 grid grid-cols-2 gap-2">
                @foreach ($others as $o)
                    <li>
                        <a href="{{ route('stations.show', $o) }}" wire:navigate class="flex items-center gap-2 rounded-lg p-2 hover:bg-surface border border-transparent hover:border-line">
                            <span class="h-2.5 w-2.5 rounded-full shrink-0" style="background: {{ $o->color }}" aria-hidden="true"></span>
                            <span class="text-sm truncate">{{ $o->name }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </aside>
    </div>
</x-layouts.site>
