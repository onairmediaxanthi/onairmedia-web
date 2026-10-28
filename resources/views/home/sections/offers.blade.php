@php
    $list = $packages[$section->id] ?? collect();
    $canSeePrices = $canSeePrices ?? false;
    $unlockUrl = auth()->check() ? route('verification.notice') : route('register');
    $many = $list->count() > 3;
@endphp
@if ($list->isNotEmpty())
<section class="mx-auto max-w-7xl px-4 sm:px-6 py-16" aria-labelledby="of-{{ $section->id }}"
         x-data="{
            el: null, auto: {{ $section->setting('autoplay', true) && $many ? 'true' : 'false' }}, timer: null, atStart: true, atEnd: false,
            init() { this.el = this.$refs.track; this.update(); if (this.auto && ! window.matchMedia('(prefers-reduced-motion: reduce)').matches) this.play(); },
            step() { const c = this.el.querySelector('[data-card]'); return c ? c.getBoundingClientRect().width + 20 : this.el.clientWidth; },
            go(d) { const end = this.el.scrollLeft + this.el.clientWidth >= this.el.scrollWidth - 8;
                    if (d > 0 && end) { this.el.scrollTo({ left: 0, behavior: 'smooth' }); } else { this.el.scrollBy({ left: d * this.step(), behavior: 'smooth' }); } },
            update() { this.atStart = this.el.scrollLeft < 8; this.atEnd = this.el.scrollLeft + this.el.clientWidth >= this.el.scrollWidth - 8; },
            play() { this.stop(); this.timer = setInterval(() => this.go(1), 6000); },
            stop() { clearInterval(this.timer); },
         }"
         @mouseenter="stop()" @mouseleave="auto && play()" @focusin="stop()">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div class="max-w-2xl">
            <h2 id="of-{{ $section->id }}" class="text-3xl sm:text-4xl font-extrabold tracking-tight">{{ $section->title ?: 'Προσφορές της εποχής' }}</h2>
            @if ($section->subtitle)<p class="mt-3 text-lg text-slate">{{ $section->subtitle }}</p>@endif
        </div>
        @if ($many)
            <div class="flex gap-2">
                <button type="button" @click="stop(); go(-1)" :disabled="atStart" aria-label="Προηγούμενες προσφορές"
                        class="grid h-11 w-11 place-items-center rounded-full border border-line bg-surface hover:border-onair disabled:opacity-40">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path d="M15 6l-6 6 6 6"/></svg>
                </button>
                <button type="button" @click="stop(); go(1)" aria-label="Επόμενες προσφορές"
                        class="grid h-11 w-11 place-items-center rounded-full border border-line bg-surface hover:border-onair">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path d="M9 6l6 6-6 6"/></svg>
                </button>
            </div>
        @endif
    </div>

    <ul x-ref="track" @scroll.debounce.100ms="update()"
        class="mt-8 flex snap-x snap-mandatory gap-5 overflow-x-auto pb-4 [scrollbar-width:thin] {{ $many ? '' : 'lg:grid lg:grid-cols-3 lg:overflow-visible' }}"
        aria-label="Προσφορές">
        @foreach ($list as $p)
            @php($left = $p->daysLeft())
            @php($stations = $p->stations())
            <li data-card class="snap-start shrink-0 w-[86%] sm:w-[calc(50%-10px)] {{ $many ? 'lg:w-[calc(33.333%-14px)]' : 'lg:w-auto' }} flex flex-col overflow-hidden rounded-3xl border border-line bg-surface shadow-sm">
                <div class="relative px-6 pt-6 pb-5 text-white" style="background: linear-gradient(135deg, {{ $p->color ?: '#c80c92' }}, #121a33)">
                    <div class="flex items-start justify-between gap-3">
                        @if ($p->badge)<span class="rounded-full bg-white/95 px-3 py-1 text-xs font-extrabold" style="color: {{ $p->color ?: '#c80c92' }}">{{ $p->badge }}</span>@else<span></span>@endif
                        @if ($left !== null)
                            <span class="rounded-full bg-black/25 px-3 py-1 text-xs font-semibold">{{ $left <= 1 ? 'Λήγει σήμερα' : 'Λήγει σε '.$left.' ημέρες' }}</span>
                        @endif
                    </div>
                    <h3 class="mt-4 text-2xl font-extrabold leading-tight">{{ $p->name }}</h3>
                    @if ($p->tagline)<p class="mt-1 text-white/85">{{ $p->tagline }}</p>@endif
                    @if ($stations->isNotEmpty())
                        <div class="mt-4 flex -space-x-2" aria-label="Σταθμοί">
                            @foreach ($stations->take(6) as $st)
                                <span class="grid h-10 w-10 place-items-center overflow-hidden rounded-full border-2 border-white bg-white" title="{{ $st->name }}">
                                    @if ($st->logo_url)<img src="{{ $st->logo_url }}" alt="{{ $st->name }}" class="h-full w-full object-contain" loading="lazy">@else<span class="text-[10px] font-bold text-night">{{ mb_substr($st->name, 0, 2) }}</span>@endif
                                </span>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div class="flex flex-1 flex-col px-6 pb-6 pt-5">
                    @if ($media = $p->mediaList())
                        <ul class="flex flex-wrap gap-1.5" aria-label="Μέσα">
                            @foreach ($media as $mm)<li class="rounded-full border border-line px-2.5 py-1 text-xs font-medium">{{ \App\Models\Package::MEDIA[$mm] ?? $mm }}</li>@endforeach
                        </ul>
                    @endif
                    @if ($p->includes)
                        <p class="mt-4 text-sm font-bold uppercase tracking-wide text-slate">Περιλαμβάνει</p>
                        <ul class="mt-2 space-y-2 text-[15px]">
                            @foreach ($p->includes as $line)
                                <li class="flex gap-2.5">
                                    <svg class="mt-1 h-4 w-4 shrink-0" style="color: {{ $p->color ?: '#c80c92' }}" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M16.7 5.3a1 1 0 010 1.4l-8 8a1 1 0 01-1.4 0l-4-4a1 1 0 111.4-1.4L8 12.6l7.3-7.3a1 1 0 011.4 0z" clip-rule="evenodd"/></svg>
                                    <span>{{ is_array($line) ? ($line['line'] ?? '') : $line }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                    @if ($p->description)<p class="mt-4 text-[15px] text-slate">{{ $p->description }}</p>@endif
                    <p class="mt-4 text-sm text-slate">
                        @if ($p->duration_days)Διάρκεια καμπάνιας: {{ $p->duration_days }} ημέρες. @endif
                        @if ($p->available_until)Η προσφορά ισχύει έως {{ $p->available_until->locale('el')->isoFormat('D MMMM') }}.@endif
                    </p>

                    <div class="mt-auto pt-6">
                        @if ($canSeePrices && $p->price !== null)
                            <p class="flex flex-wrap items-baseline gap-x-2">
                                <span class="text-3xl font-extrabold freq">{{ \App\Models\Package::formatPrice($p->price) }}</span>
                                @if ($p->discount_percent)<span class="text-slate line-through freq">{{ \App\Models\Package::formatPrice($p->compare_at_price) }}</span>@endif
                                <span class="text-sm text-slate">+ ΦΠΑ</span>
                            </p>
                            <a href="{{ route('quote.builder', ['tropos' => 'packages', 'paketo' => $p->id]) }}" class="mt-4 flex justify-center rounded-full bg-onair px-5 py-3 font-semibold text-white hover:bg-onair-dark">Το θέλω</a>
                        @else
                            <a href="{{ $unlockUrl }}" class="flex items-center justify-between gap-3 rounded-2xl border border-dashed border-line px-4 py-3 hover:border-onair">
                                <span>
                                    <span class="block font-semibold">Δείτε την τιμή</span>
                                    <span class="block text-sm text-slate">{{ auth()->check() ? 'με επιβεβαίωση του email σας' : 'με δωρεάν εγγραφή επιχείρησης' }}</span>
                                </span>
                                <svg class="h-5 w-5 text-slate" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V7a4 4 0 018 0v4"/></svg>
                            </a>
                        @endif
                    </div>
                </div>
            </li>
        @endforeach
    </ul>
</section>
@endif
