<section class="mx-auto max-w-7xl px-4 sm:px-6 py-16" aria-label="{{ $section->title ?: 'Όλοι οι σταθμοί' }}">
    @if ($section->title)
        <div class="mb-8 max-w-2xl">
            <h2 class="text-3xl sm:text-4xl font-extrabold tracking-tight">{{ $section->title }}</h2>
            @if ($section->subtitle)<p class="mt-2 text-lg text-slate">{{ $section->subtitle }}</p>@endif
        </div>
    @endif
    @if ($section->setting('layout') === 'compact')
        <ul class="grid grid-cols-3 sm:grid-cols-4 lg:grid-cols-6 gap-3">
            @foreach ($stations as $s)
                <li>
                    <a href="{{ route('stations.show', $s) }}" wire:navigate class="group flex flex-col items-center gap-2 rounded-2xl bg-surface border border-line p-4 hover:border-onair">
                        <span class="h-16 w-16 grid place-items-center rounded-xl bg-white">@if ($s->logo_url)<img src="{{ $s->logo_url }}" alt="" class="max-h-12 max-w-12 object-contain" loading="lazy">@endif</span>
                        <span class="text-sm font-semibold text-center leading-tight">{{ $s->name }}</span>
                    </a>
                </li>
            @endforeach
        </ul>
    @else
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
    @endif
</section>
