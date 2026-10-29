@php($cls = match ($order->stage) {
    'received', 'payment_review' => 'bg-amber-500/15 text-amber-700 dark:text-amber-300',
    'awaiting_payment' => 'bg-onair/10 text-onair',
    'production', 'scheduled' => 'bg-blue-500/15 text-blue-700 dark:text-blue-300',
    'running' => 'bg-emerald-500/15 text-emerald-700 dark:text-emerald-300',
    default => 'bg-slate/15 text-slate',
})
<span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-bold {{ $cls }}">
    @if ($order->stage === 'running')<span class="h-1.5 w-1.5 animate-pulse rounded-full bg-current" aria-hidden="true"></span>@endif
    {{ $order->stageLabel(true) }}
</span>
