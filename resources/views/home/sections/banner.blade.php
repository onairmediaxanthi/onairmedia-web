@php
    $img = $section->setting('image') ? \Illuminate\Support\Facades\Storage::disk('public')->url($section->setting('image')) : null;
    $layout = $section->setting('layout', 'image-right');
    $color = preg_match('/^#[0-9a-fA-F]{6}$/', (string) $section->setting('color')) ? $section->setting('color') : '#121a33';
    $url = $section->setting('cta_url');
    $url = is_string($url) && preg_match('#^(/(?!/)|https://)#', $url) ? $url : null;
@endphp
<section class="mx-auto max-w-7xl px-4 sm:px-6 py-10">
    <div class="relative isolate overflow-hidden rounded-3xl text-white" style="background: {{ $color }}">
        @if ($img && $layout === 'background')
            <img src="{{ $img }}" alt="" loading="lazy" class="absolute inset-0 -z-10 h-full w-full object-cover opacity-40">
        @endif
        <div class="grid items-center gap-8 p-8 sm:p-12 {{ $img && $layout !== 'background' ? 'md:grid-cols-2' : '' }}">
            @if ($img && $layout === 'image-left')
                <img src="{{ $img }}" alt="" loading="lazy" class="w-full rounded-2xl object-cover aspect-[4/3]">
            @endif
            <div class="max-w-xl">
                @if ($section->title)<h2 class="text-3xl sm:text-4xl font-extrabold tracking-tight leading-tight">{{ $section->title }}</h2>@endif
                @if ($section->subtitle)<p class="mt-3 text-lg text-white/85">{{ $section->subtitle }}</p>@endif
                @if ($url && $section->setting('cta_label'))
                    <a href="{{ $url }}" class="mt-7 inline-flex rounded-full bg-white px-6 py-3 font-semibold text-night hover:bg-white/90">{{ $section->setting('cta_label') }}</a>
                @endif
            </div>
            @if ($img && $layout === 'image-right')
                <img src="{{ $img }}" alt="" loading="lazy" class="w-full rounded-2xl object-cover aspect-[4/3]">
            @endif
        </div>
    </div>
</section>
