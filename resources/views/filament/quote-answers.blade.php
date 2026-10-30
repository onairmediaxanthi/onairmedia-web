@php($a = $getRecord()->answers['wizard'] ?? [])
@php($aud = \App\Support\Audience::GROUPS)
<dl style="display:grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 12px; font-size: 14px">
    <div><dt style="opacity:.6">Στόχος</dt><dd>{{ \App\Services\Quotes\Recommender::GOALS[$a['goal'] ?? '']['label'] ?? '—' }}</dd></div>
    <div><dt style="opacity:.6">Ποσό (με ΦΠΑ)</dt><dd>{{ \App\Services\Quotes\QuoteCalculator::money($a['budget'] ?? 0) }}</dd></div>
    <div><dt style="opacity:.6">Ημέρες</dt><dd>{{ $a['days'] ?? '—' }}</dd></div>
    <div><dt style="opacity:.6">Ηλικίες</dt><dd>{{ collect($a['ages'] ?? [])->map(fn ($k) => $aud['ages']['options'][$k] ?? $k)->implode(', ') ?: 'Όλες' }}</dd></div>
    <div><dt style="opacity:.6">Φύλο</dt><dd>{{ \App\Support\Audience::GENDER[$a['gender'] ?? ''] ?? 'Όλοι' }}</dd></div>
    <div><dt style="opacity:.6">Προφίλ</dt><dd>{{ collect($a['profiles'] ?? [])->map(fn ($k) => $aud['profiles']['options'][$k] ?? $k)->implode(', ') ?: '—' }}</dd></div>
    <div><dt style="opacity:.6">Μέσα</dt><dd>{{ collect($a['media'] ?? [])->map(fn ($k) => \App\Services\Quotes\Recommender::MEDIA[$k] ?? $k)->implode(', ') }}</dd></div>
    <div><dt style="opacity:.6">Υλικό</dt><dd>{{ ($a['material'] ?? '') === 'need' ? 'Χρειάζεται δημιουργικό' : 'Έχει έτοιμο' }} · σποτ {{ $a['seconds'] ?? 30 }}″</dd></div>
</dl>
