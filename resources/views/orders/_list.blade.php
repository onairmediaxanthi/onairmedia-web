<ul class="divide-y divide-line">
    @foreach ($orders as $order)
        <li>
            <a href="{{ route('orders.show', $order) }}" class="flex flex-wrap items-center justify-between gap-3 py-4 hover:text-onair">
                <span class="min-w-0">
                    <span class="block font-semibold">{{ $order->number }} · {{ $order->quote?->title }}</span>
                    <span class="block text-sm text-slate">
                        {{ $order->created_at->format('d/m/Y') }}
                        @if ($order->starts_on && in_array($order->stage, ['scheduled', 'running', 'completed'], true)) · {{ $order->starts_on->format('d/m') }}–{{ $order->ends_on?->format('d/m/Y') }}@endif
                    </span>
                </span>
                <span class="flex items-center gap-3">
                    <span class="font-semibold freq">{{ $order->money() }}</span>
                    @include('orders._stage')
                </span>
            </a>
        </li>
    @endforeach
</ul>
