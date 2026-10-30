<x-filament-panels::page>
    <div style="display:flex;flex-wrap:wrap;gap:.75rem;align-items:end">
        <label>
            <span style="display:block;font-size:.8rem;font-weight:600;margin-bottom:.25rem">Σταθμός</span>
            <x-filament::input.wrapper><x-filament::input.select wire:model.live="station">
                @foreach ($stations as $s)<option value="{{ $s->id }}">{{ $s->name }}</option>@endforeach
            </x-filament::input.select></x-filament::input.wrapper>
        </label>
        <label>
            <span style="display:block;font-size:.8rem;font-weight:600;margin-bottom:.25rem">Λίστα</span>
            <x-filament::input.wrapper><x-filament::input.select wire:model.live="period">
                <option value="month">Του μήνα</option><option value="year">Του έτους</option><option value="week">Της εβδομάδας</option>
            </x-filament::input.select></x-filament::input.wrapper>
        </label>
        <label>
            <span style="display:block;font-size:.8rem;font-weight:600;margin-bottom:.25rem">Περίοδος</span>
            <x-filament::input.wrapper><x-filament::input.select wire:model.live="which">
                @forelse ($options as $k => $label)<option value="{{ $k }}">{{ $label }}</option>@empty<option value="">Δεν υπάρχει ακόμα</option>@endforelse
            </x-filament::input.select></x-filament::input.wrapper>
        </label>
        @if ($current)
            <a href="{{ route('stations.show', $current) }}" target="_blank" style="font-size:.85rem;font-weight:600;color:#c80c92;padding-bottom:.55rem">Δημόσια σελίδα ↗</a>
        @endif
    </div>

    <x-filament::section>
        @if ($note)<p style="font-size:.85rem;opacity:.7;margin-bottom:.6rem">{{ $note }}</p>@endif
        @if ($rows->isEmpty())
            <p style="opacity:.7">Δεν υπάρχουν ακόμα δεδομένα για αυτή την επιλογή.@if ($period === 'year') Το Top 40 του έτους βγαίνει από τα μηνιαία Top 40, που δημιουργούνται την 1η κάθε μήνα.@endif</p>
        @else
            <table style="width:100%;border-collapse:collapse">
                <thead><tr style="text-align:left;font-size:.75rem;opacity:.65">
                    <th style="padding:.4rem">#</th><th style="padding:.4rem">Τραγούδι</th><th style="padding:.4rem;text-align:right">Μεταδόσεις</th>
                    @if ($period === 'year')
                        <th style="padding:.4rem;text-align:right">Μήνες στο Top</th><th style="padding:.4rem;text-align:right">Καλύτερη θέση</th>
                    @else
                        <th style="padding:.4rem;text-align:center">Κίνηση</th><th style="padding:.4rem;text-align:right">{{ $period === 'month' ? 'Μήνες' : 'Εβδομάδες' }} · κορυφή</th>
                    @endif
                </tr></thead>
                <tbody>
                @foreach ($rows as $r)
                    <tr style="border-top:1px solid rgba(127,127,127,.15)">
                        <td style="padding:.5rem .4rem;font-weight:800;font-size:1.05rem;width:2.5rem">{{ $r->position }}</td>
                        <td style="padding:.5rem .4rem"><strong>{{ $r->song?->title }}</strong><div style="font-size:.8rem;opacity:.65">{{ $r->song?->artist }}</div></td>
                        <td style="padding:.5rem .4rem;text-align:right">{{ $r->plays }}</td>
                        @if ($period === 'year')
                            <td style="padding:.5rem .4rem;text-align:right">{{ $r->months }}</td>
                            <td style="padding:.5rem .4rem;text-align:right">#{{ $r->best }}</td>
                        @else
                            @php($mv = $r->prev_position === null ? ['ΝΕΟ', '#c80c92'] : ($r->prev_position > $r->position ? ['▲ '.($r->prev_position - $r->position), '#16a34a'] : ($r->prev_position < $r->position ? ['▼ '.($r->position - $r->prev_position), '#dc2626'] : ['=', '#9ca3af'])))
                            <td style="padding:.5rem .4rem;text-align:center;font-weight:700;font-size:.8rem;color:{{ $mv[1] }}">{{ $mv[0] }}</td>
                            <td style="padding:.5rem .4rem;text-align:right;font-size:.85rem">{{ $r->periods_on ? $r->periods_on.' · #'.$r->peak : '—' }}</td>
                        @endif
                    </tr>
                @endforeach
                </tbody>
            </table>
        @endif
    </x-filament::section>

    <x-filament::section>
        <x-slot name="heading">Αναζήτηση στο αρχείο</x-slot>
        <x-slot name="description">Σε ποιους μήνες και σε ποια θέση μπήκε ένα τραγούδι ή καλλιτέχνης, σε όλους τους σταθμούς.</x-slot>
        <x-filament::input.wrapper>
            <x-filament::input type="search" wire:model.live.debounce.400ms="search" placeholder="Τίτλος ή καλλιτέχνης…" />
        </x-filament::input.wrapper>
        @if (mb_strlen(trim($search)) >= 2)
            @if ($found->isEmpty())
                <p style="opacity:.7;margin-top:.7rem">Δεν βρέθηκε στα μηνιαία Top 40.</p>
            @else
                <table style="width:100%;border-collapse:collapse;margin-top:.7rem">
                    <thead><tr style="text-align:left;font-size:.75rem;opacity:.65"><th style="padding:.4rem">Μήνας</th><th style="padding:.4rem">Σταθμός</th><th style="padding:.4rem">#</th><th style="padding:.4rem">Τραγούδι</th><th style="padding:.4rem;text-align:right">Μεταδόσεις</th></tr></thead>
                    <tbody>
                    @foreach ($found as $f)
                        <tr style="border-top:1px solid rgba(127,127,127,.15)">
                            <td style="padding:.45rem .4rem">{{ \App\Models\Chart::periodLabel('month', $f->period_start) }}</td>
                            <td style="padding:.45rem .4rem">{{ $f->station?->name }}</td>
                            <td style="padding:.45rem .4rem;font-weight:800">{{ $f->position }}</td>
                            <td style="padding:.45rem .4rem"><strong>{{ $f->song?->title }}</strong> <span style="opacity:.65">· {{ $f->song?->artist }}</span></td>
                            <td style="padding:.45rem .4rem;text-align:right">{{ $f->plays }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            @endif
        @endif
    </x-filament::section>
</x-filament-panels::page>
