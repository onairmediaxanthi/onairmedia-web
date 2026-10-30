@php
    $o = $getRecord();
    $step = $o->step();
    $info = $o->stageInfo();
@endphp
{{-- Χωρίς δικό μας theme στη διαχείριση: inline styles για να δουλεύει σε φωτεινό και σκοτεινό θέμα --}}
<div>
    <div style="display:flex;align-items:center;gap:.75rem;flex-wrap:wrap">
        <x-filament::badge :color="$info['color']" :icon="$info['icon']" size="lg">{{ $info['staff'] }}</x-filament::badge>
        <span style="opacity:.75">{{ $info['text'] }}</span>
    </div>
    @if ($o->isCancelled())
        <p style="margin-top:1rem"><strong>Αιτία ακύρωσης:</strong> {{ $o->cancel_reason }}</p>
    @else
        <ol style="display:grid;grid-template-columns:repeat({{ count(\App\Models\Order::STEPS) }},minmax(0,1fr));gap:.4rem;margin-top:1.25rem;list-style:none;padding:0">
            @foreach (\App\Models\Order::STEPS as $i => $label)
                @php($state = $i < $step ? 'done' : ($i === $step ? 'now' : 'next'))
                <li style="text-align:center;font-size:.8rem;line-height:1.2">
                    <div style="height:.4rem;border-radius:999px;background:{{ $state === 'next' ? 'rgba(127,127,127,.25)' : ($state === 'now' ? '#c80c92' : '#16a34a') }}"></div>
                    <div style="margin-top:.45rem;{{ $state === 'now' ? 'font-weight:700' : 'opacity:.7' }}">{{ $state === 'done' ? '✓ ' : '' }}{{ $label }}</div>
                </li>
            @endforeach
        </ol>
    @endif
</div>
