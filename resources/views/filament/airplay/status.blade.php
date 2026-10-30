<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">Τώρα στον αέρα</x-slot>
        <x-slot name="description">
            Καταγραφή κάθε λεπτό από το live24{{ $enabled ? '' : ' · ΣΕ ΠΑΥΣΗ (Ρυθμίσεις προσφορών)' }} ·
            {{ number_format($usage['rows']['airplays'], 0, ',', '.') }} μεταδόσεις, {{ number_format($usage['rows']['songs'], 0, ',', '.') }} τραγούδια ·
            περίπου {{ number_format($usage['mb'], 1, ',', '.') }} MB · οι αναλυτικές μεταδόσεις κρατιούνται {{ $keep }} ημέρες
        </x-slot>
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(15rem,1fr));gap:.6rem">
            @foreach ($stations as $s)
                @php($stale = ! $s->last_seen_at || $s->last_seen_at->lt(now()->subMinutes(30)))
                <div style="border:1px solid rgba(127,127,127,.2);border-radius:.75rem;padding:.65rem .8rem;border-left:4px solid {{ $s->color ?: '#c80c92' }}">
                    <div style="display:flex;justify-content:space-between;gap:.5rem">
                        <strong>{{ $s->name }}</strong>
                        <span style="font-size:.75rem;font-weight:700;color:{{ ! $s->airplay_enabled ? '#9ca3af' : ($stale ? '#d97706' : '#16a34a') }}">
                            {{ ! $s->airplay_enabled ? 'χωρίς καταγραφή' : ($stale ? 'χωρίς ενημέρωση' : '● live') }}
                        </span>
                    </div>
                    <div style="font-size:.85rem;margin-top:.2rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis" title="{{ $s->last_title }}">{{ $s->last_title ?: '—' }}</div>
                    <div style="font-size:.75rem;opacity:.65;margin-top:.15rem">
                        {{ (int) ($today[$s->id] ?? 0) }} τραγούδια σήμερα
                        @if ($s->last_seen_at) · {{ $s->last_seen_at->locale('el')->diffForHumans() }}@endif
                    </div>
                </div>
            @endforeach
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
