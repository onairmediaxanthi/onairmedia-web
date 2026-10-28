@php
    $min = 87.5; $max = 108.0;
    $pct = fn ($f) => round(((float) $f - $min) / ($max - $min) * 100, 3);
    // Κοντινοί σταθμοί: εναλλάξ πάνω/κάτω ώστε να μην πέφτουν τα ονόματα το ένα πάνω στο άλλο
    $placed = []; $prevF = null; $prevUp = false;
    foreach ($stations->sortBy('frequency') as $s) {
        $up = $prevF !== null && ((float) $s->frequency - $prevF) < 1.2 ? ! $prevUp : true;
        $placed[] = ['s' => $s, 'up' => $up];
        $prevF = (float) $s->frequency; $prevUp = $up;
    }
    $lead = $news[0] ?? null;
    $rest = array_slice($news, 1, 6);
    $fmtDate = fn ($iso) => $iso ? \Illuminate\Support\Carbon::parse($iso)->locale('el')->isoFormat('D MMM, HH:mm') : null;
@endphp

<x-layouts.site>
    {{-- ===== Slider προσφορών ===== --}}
    @if ($slides->isNotEmpty())
        <section x-data="heroSlider({{ $slides->count() }}, 7000)" :class="paused && 'paused'"
                 @mouseenter="pause(true)" @mouseleave="pause(false)" @focusin="pause(true)" @focusout="pause(false)"
                 class="relative isolate overflow-hidden bg-night text-white" aria-roledescription="carousel" aria-label="Προσφορές">
            <div class="relative h-[78svh] min-h-[460px] max-h-[760px]">
                @foreach ($slides as $n => $slide)
                    <article class="slide absolute inset-0 transition-opacity duration-700"
                             :data-active="i === {{ $n }}" :class="i === {{ $n }} ? 'opacity-100 z-10' : 'opacity-0 z-0'"
                             aria-roledescription="slide" aria-label="{{ $n + 1 }} από {{ $slides->count() }}"
                             :aria-hidden="i !== {{ $n }}" x-effect="$el.inert = i !== {{ $n }}">
                        <picture>
                            @if ($slide->image_mobile_url)<source media="(max-width: 640px)" srcset="{{ $slide->image_mobile_url }}">@endif
                            <img src="{{ $slide->image_url }}" alt="" class="slide-img fx-{{ $slide->effect }} absolute inset-0 h-full w-full object-cover"
                                 @if($n === 0) fetchpriority="high" @else loading="lazy" @endif>
                        </picture>
                        <div class="absolute inset-0 bg-gradient-to-r from-night/90 via-night/55 to-night/10" aria-hidden="true"></div>
                        <div class="relative h-full mx-auto max-w-7xl px-4 sm:px-6 flex items-end pb-24 sm:items-center sm:pb-0 {{ $slide->text_position === 'center' ? 'justify-center text-center' : '' }}">
                            <div class="slide-text max-w-2xl">
                                <h2 class="text-[clamp(2rem,5.2vw,4.25rem)] font-extrabold leading-[1.02] tracking-[-0.02em] [font-variation-settings:'wdth'_100]">{{ $slide->title }}</h2>
                                @if ($slide->subtitle)
                                    <p class="mt-5 text-lg sm:text-xl text-white/85 max-w-xl {{ $slide->text_position === 'center' ? 'mx-auto' : '' }}">{{ $slide->subtitle }}</p>
                                @else <span></span> @endif
                                @if ($slide->cta_label && $slide->safe_cta_url)
                                    <a href="{{ $slide->safe_cta_url }}" class="mt-8 inline-flex items-center rounded-full bg-onair px-7 py-3.5 font-semibold text-white hover:bg-onair-dark">{{ $slide->cta_label }}</a>
                                @endif
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>

            @if ($slides->count() > 1)
                <div class="absolute inset-x-0 bottom-6 z-20">
                    <div class="mx-auto max-w-7xl px-4 sm:px-6 flex gap-2" role="tablist" aria-label="Επιλογή slide">
                        @foreach ($slides as $n => $slide)
                            <button type="button" role="tab" @click="go({{ $n }})" :aria-selected="i === {{ $n }}"
                                    class="progress group h-8 flex-1 max-w-28 flex items-center" :data-active="i === {{ $n }}" :data-done="i > {{ $n }}"
                                    aria-label="{{ $slide->title }}">
                                <span class="block h-1 w-full rounded-full bg-white/25 overflow-hidden">
                                    <span class="progress-fill block h-full w-full bg-white"></span>
                                </span>
                            </button>
                        @endforeach
                    </div>
                </div>
            @endif
        </section>
    @endif

    {{-- ===== Δείκτης FM: οι σταθμοί στη συχνότητά τους ===== --}}
    <section id="stathmoi" class="bg-night text-white scroll-mt-16" aria-labelledby="dial-title">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 pt-14 pb-6">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <h2 id="dial-title" class="text-3xl sm:text-4xl font-extrabold tracking-tight">Στον αέρα τώρα</h2>
                    <p class="mt-2 text-white/70">Γυρίστε τον δείκτη: πατήστε μια συχνότητα και ακούστε.</p>
                </div>
                <p class="text-white/70 text-sm" x-data x-show="$store.radio.current" x-cloak>
                    Συντονισμένοι στον
                    <span class="font-semibold text-glow freq" x-text="$store.radio.current?.freq"></span>
                </p>
            </div>
        </div>

        <div class="overflow-x-auto overscroll-x-contain [scrollbar-width:thin]">
            <div x-data class="relative mx-auto min-w-[900px] max-w-7xl px-10 pt-16 pb-16">
                <div class="relative h-28">
                    {{-- Κλίμακα --}}
                    <div class="absolute inset-x-0 top-1/2 -translate-y-1/2 h-10" aria-hidden="true">
                        <div class="absolute inset-x-0 top-1/2 h-px bg-white/25"></div>
                        @for ($t = 88.0; $t <= 108.0; $t += 0.5)
                            @php($major = fmod($t, 2.0) == 0.0)
                            <span class="absolute top-1/2 -translate-y-1/2 w-px {{ $major ? 'h-6 bg-white/60' : (fmod($t, 1.0) == 0.0 ? 'h-4 bg-white/40' : 'h-2 bg-white/25') }}" style="left: {{ $pct($t) }}%"></span>
                            @if ($major)
                                <span class="absolute top-full -translate-x-1/2 mt-1 text-[11px] text-white/45 freq" style="left: {{ $pct($t) }}%">{{ (int) $t }}</span>
                            @endif
                        @endfor
                    </div>

                    {{-- Βελόνα --}}
                    <div class="needle absolute top-0 bottom-0 w-0.5 -translate-x-1/2 rounded bg-glow pointer-events-none"
                         :style="`left: ${(( ($store.radio.current?.f ?? 88.8) - {{ $min }}) / {{ $max - $min }} * 100)}%`" aria-hidden="true"></div>

                    {{-- Σταθμοί --}}
                    <ul class="absolute inset-0">
                        @foreach ($placed as ['s' => $s, 'up' => $up])
                            <li class="absolute top-1/2 -translate-x-1/2" style="left: {{ $pct($s->frequency) }}%">
                                <button type="button" @click="$store.radio.toggle('{{ $s->slug }}')"
                                        class="group absolute left-1/2 -translate-x-1/2 {{ $up ? 'bottom-3' : 'top-3' }} flex {{ $up ? 'flex-col-reverse' : 'flex-col' }} items-center gap-1.5 whitespace-nowrap"
                                        :aria-pressed="$store.radio.isPlaying('{{ $s->slug }}')"
                                        aria-label="{{ $s->name }}, {{ $s->frequency_label }}">
                                    <span class="h-3 w-3 rounded-full ring-2 ring-night transition-transform group-hover:scale-125"
                                          :class="$store.radio.current?.slug === '{{ $s->slug }}' ? 'scale-125' : ''"
                                          style="background: {{ $s->color }}"></span>
                                    <span class="rounded-md px-2 py-1 text-[13px] leading-tight transition-colors"
                                          :class="$store.radio.current?.slug === '{{ $s->slug }}' ? 'bg-white text-night' : 'text-white/80 group-hover:text-white group-hover:bg-white/10'">
                                        <span class="block font-semibold">{{ \Illuminate\Support\Str::of($s->name)->replaceMatches('/\s*\d+(\.\d+)?\s*(fm)?$/i', '') }}</span>
                                        <span class="block freq text-[11px] opacity-70">{{ $s->frequency_label }}</span>
                                    </span>
                                </button>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    </section>

    {{-- ===== Κάρτες σταθμών ===== --}}
    <section class="mx-auto max-w-7xl px-4 sm:px-6 py-16" aria-label="Όλοι οι σταθμοί">
        <ul x-data class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            @foreach ($stations as $s)
                <li class="relative flex flex-col rounded-2xl bg-surface border border-line overflow-hidden">
                    <span class="h-1.5" style="background: {{ $s->color }}" aria-hidden="true"></span>
                    <div class="p-5 flex-1 flex flex-col">
                        <div class="flex items-start gap-4">
                            <div class="h-16 w-16 shrink-0 grid place-items-center rounded-xl bg-white">
                                @if ($s->logo_url)<img src="{{ $s->logo_url }}" alt="" class="max-h-12 max-w-12 object-contain" loading="lazy">@endif
                            </div>
                            <div class="min-w-0">
                                <h3 class="font-bold text-lg leading-snug">
                                    <a href="{{ route('stations.show', $s) }}" wire:navigate class="after:absolute after:inset-0 hover:underline underline-offset-4">{{ $s->name }}</a>
                                </h3>
                                <p class="text-sm text-slate freq">{{ $s->frequency_label }} · {{ $s->genre }}</p>
                            </div>
                        </div>
                        <p class="mt-4 text-[15px] text-slate">{{ $s->slogan }}</p>
                        <div class="mt-auto pt-5 flex items-center gap-3">
                            <button type="button" @click="$store.radio.toggle('{{ $s->slug }}')"
                                    class="relative z-10 h-11 w-11 shrink-0 grid place-items-center rounded-full text-white"
                                    style="background: {{ $s->color }}"
                                    :aria-label="$store.radio.isPlaying('{{ $s->slug }}') ? 'Διακοπή {{ $s->name }}' : 'Ακρόαση {{ $s->name }}'">
                                <svg x-show="!$store.radio.isPlaying('{{ $s->slug }}')" class="h-5 w-5 ml-0.5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M8 5v14l11-7z"/></svg>
                                <svg x-show="$store.radio.isPlaying('{{ $s->slug }}')" x-cloak class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M6 6h12v12H6z"/></svg>
                            </button>
                            <p class="min-w-0 text-[13px] leading-snug">
                                <span class="block text-slate">Παίζει τώρα</span>
                                <span class="block truncate font-medium" x-text="$store.radio.nowLine('{{ $s->slug }}') || '—'">—</span>
                            </p>
                        </div>
                    </div>
                </li>
            @endforeach
        </ul>
    </section>

    {{-- ===== Ειδήσεις ===== --}}
    @if ($lead)
        <section id="eidiseis" class="border-t border-line bg-surface scroll-mt-16" aria-labelledby="news-title">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 py-16">
                <div class="flex flex-wrap items-end justify-between gap-4">
                    <h2 id="news-title" class="text-3xl sm:text-4xl font-extrabold tracking-tight">Η Θράκη σήμερα</h2>
                    <a href="https://thrakitoday.gr" target="_blank" rel="noopener" class="font-semibold text-onair hover:underline underline-offset-4">Όλες οι ειδήσεις στο thrakitoday.gr</a>
                </div>

                <div class="mt-8 grid gap-8 lg:grid-cols-[1.25fr_1fr]">
                    <article class="group relative">
                        @if ($lead['image'])
                            <img src="{{ $lead['image'] }}" alt="" loading="lazy" class="aspect-[16/10] w-full rounded-2xl object-cover bg-paper">
                        @endif
                        <p class="mt-4 text-sm text-slate">{{ $lead['category'] }}@if($lead['date']) · {{ $fmtDate($lead['date']) }}@endif</p>
                        <h3 class="mt-1 text-2xl sm:text-3xl font-bold leading-tight">
                            <a href="{{ $lead['url'] }}" target="_blank" rel="noopener" class="after:absolute after:inset-0 group-hover:underline underline-offset-4 decoration-2">{{ $lead['title'] }}</a>
                        </h3>
                        @if ($lead['excerpt'])<p class="mt-3 text-slate max-w-prose">{{ $lead['excerpt'] }}</p>@endif
                    </article>

                    <ul class="divide-y divide-line">
                        @foreach ($rest as $item)
                            <li class="group relative flex gap-4 py-4 first:pt-0">
                                @if ($item['image'])
                                    <img src="{{ $item['image'] }}" alt="" loading="lazy" class="h-20 w-28 shrink-0 rounded-lg object-cover bg-paper">
                                @endif
                                <div class="min-w-0">
                                    <p class="text-xs text-slate">{{ $item['category'] }}@if($item['date']) · {{ $fmtDate($item['date']) }}@endif</p>
                                    <h3 class="mt-1 font-semibold leading-snug line-clamp-3">
                                        <a href="{{ $item['url'] }}" target="_blank" rel="noopener" class="after:absolute after:inset-0 group-hover:underline underline-offset-4">{{ $item['title'] }}</a>
                                    </h3>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </section>
    @endif

    {{-- ===== Διαφήμιση ===== --}}
    <section id="diafimisi" class="mx-auto max-w-7xl px-4 sm:px-6 pt-20 scroll-mt-16" aria-labelledby="ads-title">
        <div class="max-w-2xl">
            <h2 id="ads-title" class="text-3xl sm:text-4xl font-extrabold tracking-tight">Ακούγεσαι. Φαίνεσαι. Μετράς.</h2>
            <p class="mt-3 text-lg text-slate">Τρία μέσα, ένα κοινό: όλη η Ξάνθη. Συνδυάστε τα όπως ταιριάζει στην επιχείρησή σας.</p>
        </div>
        <div class="mt-10 grid gap-px overflow-hidden rounded-2xl border border-line bg-line md:grid-cols-3">
            <div class="bg-surface p-7">
                <h3 class="text-xl font-bold">Ραδιόφωνο</h3>
                <p class="mt-2 text-slate">Σποτ σε 12 σταθμούς, από τα λαϊκά ως το rock. Τυχαίες ώρες της ζώνης ή ακριβώς τις ώρες που θέλετε.</p>
            </div>
            <div class="bg-surface p-7">
                <h3 class="text-xl font-bold">thrakitoday.gr</h3>
                <p class="mt-2 text-slate">Banner δίπλα στις ειδήσεις και χορηγούμενα άρθρα για την επιχείρησή σας.</p>
            </div>
            <div class="bg-surface p-7">
                <h3 class="text-xl font-bold">Οθόνη Πόλης</h3>
                <p class="mt-2 text-slate">Η ψηφιακή πινακίδα LED στην πόλη, με την προβολή σας όλη μέρα.</p>
            </div>
        </div>
        <p class="mt-6 text-slate">Σύντομα θα στήνετε και θα πληρώνετε την καμπάνια σας online, εδώ.</p>
    </section>
</x-layouts.site>
