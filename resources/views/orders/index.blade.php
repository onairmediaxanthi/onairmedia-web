<x-layouts.site title="Οι παραγγελίες μου — On Air Media Group">
    <section class="mx-auto max-w-4xl px-4 sm:px-6 py-12 sm:py-16">
        <a href="{{ route('account') }}" class="font-semibold text-slate hover:text-ink">← Το Studio μου</a>
        <h1 class="mt-3 text-3xl sm:text-4xl font-extrabold tracking-tight">Οι παραγγελίες μου</h1>
        <div class="mt-8 rounded-2xl border border-line bg-surface px-6">
            @if ($orders->isEmpty())
                <p class="py-8 text-slate">Δεν έχετε ακόμα παραγγελία. Όταν προχωρήσετε μια προσφορά στο ταμείο, θα βλέπετε εδώ κάθε βήμα της.</p>
            @else
                @include('orders._list')
            @endif
        </div>
        <div class="mt-6">{{ $orders->links() }}</div>
    </section>
</x-layouts.site>
