@php($cls = match (true) {
    $quote->isExpired() => 'bg-slate/15 text-slate',
    $quote->status === 'submitted' => 'bg-amber-500/15 text-amber-700 dark:text-amber-300',
    $quote->status === 'ordered' => 'bg-emerald-500/15 text-emerald-700 dark:text-emerald-300',
    default => 'bg-onair/10 text-onair',
})
<span class="inline-flex rounded-full px-3 py-1 text-xs font-bold {{ $cls }}">{{ $quote->statusLabel() }}</span>
