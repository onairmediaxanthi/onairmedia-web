@php
    $items = array_values(array_filter((array) $section->setting('items', []), fn ($i) => ! empty($i['image'])));
    $gray = $section->setting('grayscale', true);
    $marquee = $section->setting('marquee', false) && count($items) > 4;
@endphp
@if ($items)
<section class="mx-auto max-w-7xl px-4 sm:px-6 py-14" aria-labelledby="lg-{{ $section->id }}">
    @if ($section->title)<h2 id="lg-{{ $section->id }}" class="text-center text-2xl sm:text-3xl font-extrabold tracking-tight">{{ $section->title }}</h2>@endif
    @if ($section->subtitle)<p class="mt-2 text-center text-slate">{{ $section->subtitle }}</p>@endif
    <div class="mt-8 {{ $marquee ? 'overflow-hidden [mask-image:linear-gradient(90deg,transparent,#000_10%,#000_90%,transparent)]' : '' }}">
        <ul class="{{ $marquee ? 'flex w-max animate-[marquee_40s_linear_infinite] gap-12 hover:[animation-play-state:paused]' : 'flex flex-wrap items-center justify-center gap-x-12 gap-y-8' }}">
            @foreach ($marquee ? array_merge($items, $items) : $items as $i => $it)
                <li @if($marquee && $i >= count($items)) aria-hidden="true" @endif>
                    @php($img = '<img src="'.e(\Illuminate\Support\Facades\Storage::disk('public')->url($it['image'])).'" alt="'.e($it['name'] ?? '').'" loading="lazy" class="h-12 w-auto max-w-40 object-contain '.($gray ? 'opacity-70 grayscale transition hover:opacity-100 hover:grayscale-0' : '').'">')
                    @if (! empty($it['url']) && str_starts_with($it['url'], 'https://'))<a href="{{ $it['url'] }}" target="_blank" rel="noopener" title="{{ $it['name'] ?? '' }}">{!! $img !!}</a>@else{!! $img !!}@endif
                </li>
            @endforeach
        </ul>
    </div>
</section>
@endif
