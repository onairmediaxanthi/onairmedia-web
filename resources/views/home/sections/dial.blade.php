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
@endphp
<section class="bg-night text-white" aria-labelledby="dial-title-{{ $section->id }}">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 pt-14 pb-6">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h2 id="dial-title-{{ $section->id }}" class="text-3xl sm:text-4xl font-extrabold tracking-tight">{{ $section->title ?: 'Στον αέρα τώρα' }}</h2>
                <p class="mt-2 text-white/70">{{ $section->subtitle ?: 'Γυρίστε τον δείκτη: πατήστε μια συχνότητα και ακούστε.' }}</p>
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
