<x-layouts.site title="Οι προσφορές μου — On Air Media Group">
    <section class="mx-auto max-w-4xl px-4 sm:px-6 py-12 sm:py-16">
        <a href="{{ route('account') }}" class="font-semibold text-slate hover:text-ink">← Το Studio μου</a>
        <div class="mt-3 flex flex-wrap items-end justify-between gap-4">
            <h1 class="text-3xl sm:text-4xl font-extrabold tracking-tight">Οι προσφορές μου</h1>
            <a href="{{ route('quote.builder') }}" class="rounded-full bg-onair px-5 py-2.5 font-semibold text-white hover:bg-onair-dark">Νέα προσφορά</a>
        </div>
        @if (session('status'))<p class="mt-6 rounded-xl border border-emerald-500/40 bg-emerald-500/10 px-4 py-3" role="status">{{ session('status') }}</p>@endif
        <div class="mt-8 rounded-2xl border border-line bg-surface px-6">
            @if ($quotes->isEmpty())
                <p class="py-8 text-slate">Δεν έχετε αποθηκευμένες προσφορές. <a href="{{ route('quote.builder') }}" class="font-semibold text-onair hover:underline">Φτιάξτε την πρώτη σας</a>.</p>
            @else
                @include('quotes._list')
            @endif
        </div>
        <div class="mt-6">{{ $quotes->links() }}</div>
    </section>
</x-layouts.site>
