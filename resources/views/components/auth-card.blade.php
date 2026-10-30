@props(['title', 'intro' => null, 'wide' => false])
<section class="px-4 py-12 sm:py-16">
    <div class="mx-auto {{ $wide ? 'max-w-3xl' : 'max-w-md' }}">
        <h1 class="text-3xl sm:text-4xl font-extrabold tracking-tight">{{ $title }}</h1>
        @if ($intro)<p class="mt-3 text-lg text-slate">{{ $intro }}</p>@endif
        @if (session('status'))
            <p class="mt-6 rounded-xl border border-emerald-500/40 bg-emerald-500/10 px-4 py-3 text-[15px]" role="status">{{ session('status') }}</p>
        @endif
        <div class="mt-8 rounded-2xl border border-line bg-surface p-6 sm:p-8">
            {{ $slot }}
        </div>
        @isset($footer)<div class="mt-6 text-center text-[15px] text-slate">{{ $footer }}</div>@endisset
    </div>
</section>
