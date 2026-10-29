@php($files = $getRecord()->files()->with('user')->get())
@if ($files->isEmpty())
    <p style="opacity:.7">Δεν έχει ανέβει υλικό ακόμα.@if($getRecord()->stage === 'materials') Ο πελάτης έχει ειδοποιηθεί να το ανεβάσει.@endif</p>
@else
    <ul style="list-style:none;padding:0;margin:0">
        @foreach ($files as $f)
            <li style="display:flex;justify-content:space-between;gap:1rem;align-items:center;padding:.55rem 0;{{ $loop->last ? '' : 'border-bottom:1px solid rgba(127,127,127,.18)' }}">
                <div style="min-width:0">
                    <a href="{{ route('admin.order-file', $f) }}" style="font-weight:600;color:#c80c92;word-break:break-all">{{ $f->name }}</a>
                    <div style="font-size:.8rem;opacity:.65">
                        {{ strtoupper($f->extension()) }} · {{ $f->sizeLabel() }} · {{ $f->from === 'customer' ? 'από τον πελάτη' : 'από '.($f->user?->name ?? 'την ομάδα') }} · {{ $f->created_at->format('d/m/Y H:i') }}
                    </div>
                </div>
                @if (in_array($f->extension(), ['mp3', 'wav', 'm4a', 'aac', 'ogg'], true))
                    <audio controls preload="none" src="{{ route('admin.order-file', $f) }}" style="height:2rem;max-width:14rem"></audio>
                @endif
            </li>
        @endforeach
    </ul>
@endif
