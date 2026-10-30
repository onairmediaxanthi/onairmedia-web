<ul class="divide-y divide-line">
    @foreach ($quotes as $quote)
        <li>
            <a href="{{ route('quotes.show', $quote) }}" class="flex flex-wrap items-center gap-x-4 gap-y-1 py-4 hover:text-onair">
                <span class="flex-1 min-w-48">
                    <span class="block font-semibold">{{ $quote->title }}</span>
                    <span class="block text-sm text-slate">{{ $quote->number }} · {{ $quote->created_at->format('d/m/Y') }} · {{ $quote->days }} ημέρες</span>
                </span>
                @include('quotes._status')
                <span class="w-28 text-right font-bold freq">{{ $quote->money('total') }}</span>
            </a>
        </li>
    @endforeach
</ul>
