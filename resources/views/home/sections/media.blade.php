@php
    $items = $section->setting('items') ?: [
        ['eyebrow' => 'Ακούγεσαι', 'title' => 'Ραδιόφωνο', 'icon' => 'radio', 'color' => '#c80c92', 'stat' => '12', 'stat_label' => 'σταθμοί FM',
            'text' => 'Σποτ και αναφορές σε δώδεκα σταθμούς, από τα λαϊκά ως το rock. Σε τυχαίες ώρες της ζώνης ή ακριβώς τις ώρες που θέλετε.', 'url' => '#stathmoi', 'cta' => 'Οι σταθμοί μας'],
        ['eyebrow' => 'Φαίνεσαι', 'title' => 'Οθόνη Πόλης', 'icon' => 'screen', 'color' => '#f59e0b', 'stat' => null, 'stat_label' => null,
            'text' => 'Η ψηφιακή πινακίδα LED στην πόλη. Το μήνυμά σας στα μάτια όσων περνούν, όλη μέρα.', 'url' => null, 'cta' => null],
        ['eyebrow' => 'Μετράς', 'title' => 'thrakitoday.gr', 'icon' => 'news', 'color' => '#2563eb', 'stat' => '24/7', 'stat_label' => 'ενημέρωση',
            'text' => 'Banner δίπλα στις ειδήσεις και χορηγούμενα άρθρα: η επιχείρησή σας εκεί που η Θράκη ενημερώνεται, κάθε μέρα.', 'url' => 'https://thrakitoday.gr', 'cta' => 'Στο thrakitoday.gr'],
    ];
    $safe = fn ($u) => is_string($u) && preg_match('#^(/(?!/)|https://|\#[a-z0-9-]+$)#i', $u) ? $u : null;
    $hex = fn ($c) => is_string($c) && preg_match('/^#[0-9a-fA-F]{6}$/', $c) ? $c : '#c80c92';
    $layout = $section->setting('layout', 'cards');
    $title = $section->title ?: 'Ακούγεσαι. Φαίνεσαι. Μετράς.';
    // «Ακούγεσαι. Φαίνεσαι. Μετράς.» → κάθε λέξη με το δικό της χρώμα
    $parts = array_values(array_filter(array_map('trim', preg_split('/(?<=\.)\s+/u', $title))));
    $partColors = array_map(fn ($i) => $hex($i['color'] ?? null), $items);
    $stations = $section->setting('show_logos', true) ? \App\Models\Station::query()->active()->whereNotNull('logo_path')->orderBy('sort')->limit(8)->get() : collect();
    $cols = count($items) >= 3 ? 'lg:grid-cols-3' : (count($items) === 2 ? 'lg:grid-cols-2' : '');
@endphp
<section class="mx-auto max-w-7xl px-4 sm:px-6 py-20" aria-labelledby="media-title-{{ $section->id }}">
    <div class="max-w-3xl">
        <p class="inline-flex items-center gap-2 rounded-full border border-line px-3 py-1 text-sm font-semibold text-slate">
            <span class="h-2 w-2 rounded-full bg-onair" aria-hidden="true"></span> Τα μέσα μας
        </p>
        <h2 id="media-title-{{ $section->id }}" class="mt-4 text-4xl sm:text-5xl font-extrabold tracking-tight leading-[1.05]">
            @if (count($parts) > 1)
                @foreach ($parts as $i => $p)<span class="inline-block" @if($i > 0) style="color: {{ $partColors[$i] ?? '#c80c92' }}" @endif>{{ $p }}</span>@if(! $loop->last) @endif @endforeach
            @else
                {{ $title }}
            @endif
        </h2>
        <p class="mt-4 text-lg text-slate">{{ $section->subtitle ?: 'Τρία μέσα, ένα κοινό: όλη η Ξάνθη. Συνδυάστε τα όπως ταιριάζει στην επιχείρησή σας.' }}</p>
    </div>

    @if ($layout === 'minimal')
        <div class="mt-10 grid gap-px overflow-hidden rounded-2xl border border-line bg-line sm:grid-cols-2 {{ $cols }}">
            @foreach ($items as $item)
                <div class="relative bg-surface p-7">
                    <h3 class="text-xl font-bold">
                        @if ($u = $safe($item['url'] ?? null))<a href="{{ $u }}" class="after:absolute after:inset-0 hover:text-onair">{{ $item['title'] }}</a>@else{{ $item['title'] }}@endif
                    </h3>
                    <p class="mt-2 text-slate">{{ $item['text'] }}</p>
                </div>
            @endforeach
        </div>
    @elseif ($layout === 'rows')
        <div class="mt-12 space-y-8">
            @foreach ($items as $i => $item)
                @php($c = $hex($item['color'] ?? null))
                @php($img = ! empty($item['image']) ? \Illuminate\Support\Facades\Storage::disk('public')->url($item['image']) : null)
                <article class="grid items-center gap-8 overflow-hidden rounded-3xl border border-line bg-surface md:grid-cols-2">
                    <div class="relative aspect-[16/10] h-full min-h-56 {{ $i % 2 ? 'md:order-2' : '' }}" style="background: linear-gradient(135deg, {{ $c }}, #121a33)">
                        @if ($img)
                            <img src="{{ $img }}" alt="" loading="lazy" class="absolute inset-0 h-full w-full object-cover">
                        @else
                            <x-ui-icon :name="$item['icon'] ?? 'star'" class="absolute left-1/2 top-1/2 h-24 w-24 -translate-x-1/2 -translate-y-1/2 text-white/90" />
                        @endif
                    </div>
                    <div class="p-8 sm:p-10">
                        @if (! empty($item['eyebrow']))<p class="text-sm font-extrabold uppercase tracking-[.2em]" style="color: {{ $c }}">{{ $item['eyebrow'] }}</p>@endif
                        <h3 class="mt-2 text-3xl font-extrabold">{{ $item['title'] }}</h3>
                        <p class="mt-3 text-lg text-slate">{{ $item['text'] }}</p>
                        @if (! empty($item['stat']))<p class="mt-5"><span class="text-4xl font-extrabold freq" style="color: {{ $c }}">{{ $item['stat'] }}</span> <span class="text-slate">{{ $item['stat_label'] ?? '' }}</span></p>@endif
                        @if ($u = $safe($item['url'] ?? null))
                            <a href="{{ $u }}" class="mt-6 inline-flex items-center gap-2 font-semibold hover:underline" style="color: {{ $c }}">{{ $item['cta'] ?? 'Μάθετε περισσότερα' }} <x-ui-icon name="arrow" class="h-4 w-4" /></a>
                        @endif
                    </div>
                </article>
            @endforeach
        </div>
    @else
        <div class="mt-12 grid gap-6 sm:grid-cols-2 {{ $cols }}">
            @foreach ($items as $i => $item)
                @php($c = $hex($item['color'] ?? null))
                <article class="group relative flex flex-col overflow-hidden rounded-3xl border border-line bg-surface p-7 shadow-sm transition duration-300 hover:-translate-y-1 hover:shadow-xl">
                    <div class="pointer-events-none absolute -right-16 -top-16 h-48 w-48 rounded-full opacity-[.13] blur-2xl transition-opacity group-hover:opacity-25" style="background: {{ $c }}" aria-hidden="true"></div>
                    <div class="absolute inset-x-0 top-0 h-1.5" style="background: linear-gradient(90deg, {{ $c }}, transparent)" aria-hidden="true"></div>

                    <div class="flex items-start justify-between gap-4">
                        <span class="grid h-14 w-14 place-items-center rounded-2xl text-white shadow-lg" style="background: linear-gradient(135deg, {{ $c }}, {{ $c }}cc); box-shadow: 0 10px 25px -10px {{ $c }}">
                            <x-ui-icon :name="$item['icon'] ?? 'star'" class="h-7 w-7" />
                        </span>
                        @if (! empty($item['stat']))
                            <span class="text-right leading-none">
                                <span class="block text-4xl font-extrabold freq" style="color: {{ $c }}">{{ $item['stat'] }}</span>
                                <span class="mt-1 block text-sm text-slate">{{ $item['stat_label'] ?? '' }}</span>
                            </span>
                        @endif
                    </div>

                    @if (! empty($item['eyebrow']))<p class="mt-6 text-sm font-extrabold uppercase tracking-[.2em]" style="color: {{ $c }}">{{ $item['eyebrow'] }}</p>@endif
                    <h3 class="mt-1 text-2xl font-extrabold">{{ $item['title'] }}</h3>
                    <p class="mt-3 flex-1 text-slate">{{ $item['text'] }}</p>

                    @if (($item['icon'] ?? null) === 'radio' && $stations->isNotEmpty())
                        <div class="mt-5 flex -space-x-2" aria-label="Οι σταθμοί μας">
                            @foreach ($stations as $st)
                                <span class="grid h-9 w-9 place-items-center overflow-hidden rounded-full border-2 border-surface bg-white shadow-sm" title="{{ $st->name }}">
                                    <img src="{{ $st->logo_url }}" alt="{{ $st->name }}" class="h-full w-full object-contain" loading="lazy">
                                </span>
                            @endforeach
                        </div>
                    @endif

                    @if ($u = $safe($item['url'] ?? null))
                        <a href="{{ $u }}" class="mt-6 inline-flex items-center gap-2 font-semibold after:absolute after:inset-0" style="color: {{ $c }}"
                           @if(str_starts_with($u, 'https://')) target="_blank" rel="noopener" @endif>
                            {{ $item['cta'] ?? 'Μάθετε περισσότερα' }} <x-ui-icon name="arrow" class="h-4 w-4 transition-transform group-hover:translate-x-1" />
                        </a>
                    @endif
                </article>
            @endforeach
        </div>
    @endif

    @if ($section->setting('show_cta', true))
        <div class="mt-10 flex flex-col items-start justify-between gap-5 rounded-3xl bg-night p-7 text-white sm:flex-row sm:items-center sm:p-9">
            <div>
                <p class="text-2xl font-extrabold">Συνδυάστε τα. Ο ίδιος πελάτης σας συναντά παντού.</p>
                <p class="mt-1 text-white/70">{{ $section->setting('note') ?: 'Πείτε μας ποσό και κοινό, και σας προτείνουμε τον καλύτερο συνδυασμό σε δύο λεπτά.' }}</p>
            </div>
            <a href="{{ route('quote.builder') }}" class="inline-flex shrink-0 items-center gap-2 whitespace-nowrap rounded-full bg-onair px-6 py-3.5 font-semibold text-white hover:bg-onair-dark">
                Βγάλτε προσφορά <x-ui-icon name="arrow" class="h-4 w-4" />
            </a>
        </div>
    @elseif ($note = $section->setting('note'))
        <p class="mt-6 text-slate">{{ $note }}</p>
    @endif
</section>
