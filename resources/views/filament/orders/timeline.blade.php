@php($events = $getRecord()->events()->with('user')->get())
<ol style="list-style:none;padding:0;margin:0">
    @forelse ($events as $e)
        <li style="display:flex;gap:.85rem;padding:.75rem 0;{{ $loop->last ? '' : 'border-bottom:1px solid rgba(127,127,127,.18)' }}">
            <span style="flex:none;margin-top:.35rem;height:.6rem;width:.6rem;border-radius:999px;background:{{ $e->internal ? '#9ca3af' : ($e->stage ? '#c80c92' : '#2563eb') }}"></span>
            <div style="min-width:0;flex:1">
                <div style="display:flex;justify-content:space-between;gap:1rem;flex-wrap:wrap">
                    <strong>{{ $e->title }}</strong>
                    <span style="opacity:.65;font-size:.85rem;white-space:nowrap">{{ $e->created_at?->format('d/m/Y H:i') }}</span>
                </div>
                @if ($e->note)<p style="margin-top:.25rem;white-space:pre-line">{{ $e->note }}</p>@endif
                <p style="margin-top:.25rem;font-size:.8rem;opacity:.65">
                    {{ $e->user?->name ?? (in_array($e->stage, ['received', 'payment_review'], true) || \Illuminate\Support\Str::startsWith($e->title, ['Ανεβάσατε', 'Στείλατε', 'Δηλώσατε']) ? 'Ο πελάτης' : 'Αυτόματα') }}
                    @if ($e->internal) · <strong>εσωτερική σημείωση</strong>@elseif ($e->notified) · ✉ στάλθηκε email στον πελάτη @endif
                </p>
            </div>
        </li>
    @empty
        <li style="opacity:.7">Καμία καταγραφή ακόμα.</li>
    @endforelse
</ol>
