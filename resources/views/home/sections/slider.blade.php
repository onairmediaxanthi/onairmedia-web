@php
    $height = match ($section->setting('height')) {
        'short' => 'h-[52svh] min-h-[360px] max-h-[520px]',
        'full' => 'h-[calc(100svh-4rem)] min-h-[480px]',
        default => 'h-[78svh] min-h-[460px] max-h-[760px]',
    };
@endphp
@if ($slides->isNotEmpty())
    <section x-data="heroSlider({{ $slides->count() }}, {{ (int) $section->setting('interval', 7) * 1000 }})" :class="paused && 'paused'"
             @mouseenter="pause(true)" @mouseleave="pause(false)" @focusin="pause(true)" @focusout="pause(false)"
             class="relative isolate overflow-hidden bg-night text-white" aria-roledescription="carousel" aria-label="Προσφορές">
        <div class="relative {{ $height }}">
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
