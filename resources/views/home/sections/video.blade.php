@php
    $raw = (string) $section->setting('url');
    $embed = null;
    if (preg_match('~(?:youtube\.com/(?:watch\?v=|embed/|shorts/)|youtu\.be/)([A-Za-z0-9_-]{11})~', $raw, $m)) {
        $embed = 'https://www.youtube-nocookie.com/embed/'.$m[1].'?autoplay=1&rel=0';
        $thumb = 'https://i.ytimg.com/vi/'.$m[1].'/hqdefault.jpg';
    } elseif (preg_match('~vimeo\.com/(\d+)~', $raw, $m)) {
        $embed = 'https://player.vimeo.com/video/'.$m[1].'?autoplay=1&dnt=1';
        $thumb = null;
    }
    $poster = $section->setting('poster') ? \Illuminate\Support\Facades\Storage::disk('public')->url($section->setting('poster')) : null;
@endphp
@if ($embed)
<section class="mx-auto max-w-5xl px-4 sm:px-6 py-16" aria-labelledby="vd-{{ $section->id }}">
    @if ($section->title)<h2 id="vd-{{ $section->id }}" class="text-3xl sm:text-4xl font-extrabold tracking-tight">{{ $section->title }}</h2>@endif
    @if ($section->subtitle)<p class="mt-3 text-lg text-slate">{{ $section->subtitle }}</p>@endif
    <div class="relative mt-8 aspect-video overflow-hidden rounded-3xl bg-night shadow-xl" x-data="{ play: false }">
        <template x-if="play"><iframe :src="'{{ $embed }}'" class="absolute inset-0 h-full w-full" allow="autoplay; fullscreen; picture-in-picture" allowfullscreen title="{{ $section->title ?: 'Βίντεο' }}"></iframe></template>
        <button type="button" x-show="! play" @click="play = true" class="group absolute inset-0 grid place-items-center" aria-label="Αναπαραγωγή βίντεο">
            @if ($poster)<img src="{{ $poster }}" alt="" class="absolute inset-0 h-full w-full object-cover" loading="lazy">@endif
            <span class="absolute inset-0 bg-gradient-to-t from-night/70 to-transparent"></span>
            <span class="relative grid h-20 w-20 place-items-center rounded-full bg-onair text-white shadow-2xl transition-transform group-hover:scale-110"><x-ui-icon name="play" class="ml-1 h-8 w-8" /></span>
            <span class="absolute bottom-4 left-0 right-0 text-center text-xs text-white/70">Με την αναπαραγωγή φορτώνεται το βίντεο από {{ str_contains($embed, 'vimeo') ? 'το Vimeo' : 'το YouTube' }}</span>
        </button>
    </div>
    @if ($section->setting('caption'))<p class="mt-4 text-slate">{{ $section->setting('caption') }}</p>@endif
</section>
@endif
