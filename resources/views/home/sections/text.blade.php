@php($body = $section->setting('body') ? \Illuminate\Support\Str::sanitizeHtml($section->setting('body')) : null)
<section class="mx-auto max-w-7xl px-4 sm:px-6 py-14 {{ $section->setting('align') === 'center' ? 'text-center' : '' }}">
    <div class="{{ $section->setting('align') === 'center' ? 'mx-auto' : '' }} max-w-3xl">
        @if ($section->title)<h2 class="text-3xl sm:text-4xl font-extrabold tracking-tight">{{ $section->title }}</h2>@endif
        @if ($section->subtitle)<p class="mt-3 text-lg text-slate">{{ $section->subtitle }}</p>@endif
        @if ($body)
            <div class="mt-5 text-[17px] leading-relaxed [&_a]:text-onair [&_a]:underline [&_h2]:mt-8 [&_h2]:text-2xl [&_h2]:font-bold [&_h3]:mt-6 [&_h3]:text-xl [&_h3]:font-bold [&_p]:mt-4 [&_ul]:mt-4 [&_ul]:list-disc [&_ul]:pl-6 [&_ol]:mt-4 [&_ol]:list-decimal [&_ol]:pl-6 [&_blockquote]:mt-4 [&_blockquote]:border-l-4 [&_blockquote]:border-onair [&_blockquote]:pl-4 [&_blockquote]:italic">{!! $body !!}</div>
        @endif
    </div>
</section>
