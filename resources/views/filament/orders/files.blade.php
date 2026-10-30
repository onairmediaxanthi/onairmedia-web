@php
    $o = $getRecord();
    $plan = \App\Services\Orders\MaterialPlan::for($o);
    $status = \App\Services\Orders\MaterialPlan::status($o, $plan);
    $files = $o->files()->with('user')->get()->groupBy(fn ($f) => $f->slot ?? '_');
    $brief = (array) ($o->brief ?? []);
    $audio = ['mp3', 'wav', 'm4a', 'aac', 'ogg'];
@endphp
@if ($o->files_purged_at)
    <p style="margin-bottom:.8rem;padding:.6rem .8rem;border-radius:.6rem;background:rgba(127,127,127,.12)">
        Τα αρχεία διαγράφηκαν από τον server στις {{ $o->files_purged_at->format('d/m/Y H:i') }} (αφού επιβεβαιώθηκε η αρχειοθέτηση). Οι εγγραφές μένουν για το ιστορικό.
    </p>
@endif
@foreach ($plan as $key => $slot)
    @php($st = $status[$key])
    <div style="padding:.75rem 0;border-bottom:1px solid rgba(127,127,127,.18)">
        <div style="display:flex;justify-content:space-between;gap:1rem;flex-wrap:wrap">
            <div>
                <strong>{{ $slot['title'] }}</strong>
                @if ($slot['we_make'])<span style="margin-left:.4rem;font-size:.75rem;font-weight:700;color:#c80c92">το φτιάχνουμε εμείς</span>@endif
                <div style="font-size:.8rem;opacity:.65">{{ $slot['subtitle'] }}</div>
            </div>
            <span style="font-size:.8rem;font-weight:700;color:{{ $st['state'] === 'done' ? '#16a34a' : ($st['state'] === 'missing' ? '#d97706' : '#9ca3af') }}">
                {{ $st['state'] === 'done' ? '✓ Έτοιμο' : ($st['state'] === 'missing' ? 'Λείπει: '.implode(', ', $st['missing']) : 'Προαιρετικό') }}
            </span>
        </div>
        @if (filled($brief[$key] ?? null))
            <div style="margin-top:.45rem;padding:.55rem .75rem;border-radius:.5rem;background:rgba(200,12,146,.07);white-space:pre-line;font-size:.9rem">{{ $brief[$key] }}</div>
        @endif
        @foreach ($files->get($key, collect()) as $f)
            <div style="display:flex;justify-content:space-between;gap:1rem;align-items:center;margin-top:.45rem">
                <div style="min-width:0">
                    @if ($f->isPurged())<span style="font-weight:600;text-decoration:line-through;opacity:.6">{{ $f->name }}</span>
                    @else<a href="{{ route('admin.order-file', $f) }}" style="font-weight:600;color:#c80c92;word-break:break-all">{{ $f->name }}</a>@endif
                    <div style="font-size:.78rem;opacity:.7">{{ $f->techLabel() }} · {{ $f->created_at->format('d/m/Y H:i') }}</div>
                    @foreach ($f->warnings() as $w)<div style="font-size:.78rem;color:#d97706">⚠ {{ $w }}</div>@endforeach
                </div>
                @if (! $f->isPurged() && in_array($f->extension(), $audio, true))
                    <audio controls preload="none" src="{{ route('admin.order-file', $f) }}" style="height:2rem;max-width:14rem"></audio>
                @endif
            </div>
        @endforeach
    </div>
@endforeach
@php($other = $files->get('_', collect()))
@if ($other->isNotEmpty())
    <div style="padding:.75rem 0">
        <strong>Άλλα αρχεία</strong>
        @foreach ($other as $f)
            <div style="margin-top:.4rem">
                @if ($f->isPurged())<span style="text-decoration:line-through;opacity:.6">{{ $f->name }}</span>
                @else<a href="{{ route('admin.order-file', $f) }}" style="font-weight:600;color:#c80c92">{{ $f->name }}</a>@endif
                <span style="font-size:.78rem;opacity:.7"> · {{ $f->from === 'customer' ? 'από τον πελάτη' : 'από '.($f->user?->name ?? 'την ομάδα') }} · {{ $f->techLabel() }}</span>
            </div>
        @endforeach
    </div>
@endif
