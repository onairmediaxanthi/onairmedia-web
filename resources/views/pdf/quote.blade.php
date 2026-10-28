@php($m = fn ($v) => \App\Services\Quotes\QuoteCalculator::money($v))
<!doctype html>
<html lang="el">
<head>
<meta charset="utf-8">
<title>{{ $quote->number }}</title>
<style>
    @page { margin: 26mm 16mm 20mm 16mm; }
    * { font-family: 'DejaVu Sans', sans-serif; }
    body { font-size: 9.5pt; color: #121a33; line-height: 1.45; }
    header { position: fixed; top: -20mm; left: 0; right: 0; height: 16mm; border-bottom: 2px solid #c80c92; }
    header img { height: 12mm; }
    header .brand { font-size: 15pt; font-weight: bold; color: #c80c92; }
    header .right { position: absolute; right: 0; top: 1mm; text-align: right; font-size: 8pt; color: #555; }
    footer { position: fixed; bottom: -15mm; left: 0; right: 0; font-size: 7.5pt; color: #777; border-top: 1px solid #ddd; padding-top: 2mm; }
    h1 { font-size: 17pt; margin: 0 0 1mm; }
    .muted { color: #666; }
    .grid { width: 100%; margin-top: 4mm; }
    .grid td { vertical-align: top; width: 50%; }
    .box { border: 1px solid #ddd; border-radius: 3mm; padding: 3mm 4mm; }
    .label { font-size: 7.5pt; text-transform: uppercase; color: #888; letter-spacing: .5px; }
    table.items { width: 100%; border-collapse: collapse; margin-top: 6mm; }
    table.items th { background: #121a33; color: #fff; text-align: left; font-size: 8pt; padding: 2mm; }
    table.items td { border-bottom: 1px solid #e5e5e5; padding: 1.6mm 2mm; vertical-align: top; }
    table.items .num { text-align: right; white-space: nowrap; }
    table.items tr.group td { background: #f6f0f4; font-weight: bold; color: #c80c92; border-bottom: none; }
    .detail { font-size: 8pt; color: #666; }
    table.totals { width: 45%; margin-left: 55%; margin-top: 3mm; border-collapse: collapse; }
    table.totals td { padding: 1.5mm 2mm; }
    table.totals .grand td { font-size: 12pt; font-weight: bold; border-top: 2px solid #c80c92; }
    .notes { page-break-inside: avoid; margin-top: 6mm; font-size: 8pt; color: #555; }
    .notes li { margin-bottom: 1mm; }
</style>
</head>
<body>
<header>
    @if ($logo)<img src="{{ $logo }}" alt="">@else<span class="brand">{{ $company['company_name'] }}</span>@endif
    <div class="right">
        {{ $company['company_name'] }}@if($company['vat']) · ΑΦΜ {{ $company['vat'] }}@endif<br>
        @if($company['address']){{ $company['address'] }}<br>@endif
        {{ collect([$company['phone'], $company['email']])->filter()->implode(' · ') }}
    </div>
</header>
<footer>
    {{ $quote->number }} · Εκδόθηκε {{ $quote->created_at->format('d/m/Y') }} · {{ preg_replace('#^https?://#', '', config('app.url')) }}
</footer>

<h1>Προσφορά διαφήμισης</h1>
<div class="muted">{{ $quote->number }} · {{ $quote->title }}</div>

<table class="grid"><tr>
    <td style="padding-right: 3mm">
        <div class="box">
            <div class="label">Πελάτης</div>
            @if ($business)
                <strong>{{ $business->legal_name }}</strong>@if($business->trade_name) ({{ $business->trade_name }})@endif<br>
                ΑΦΜ {{ $business->vat }}@if($business->tax_office) · ΔΟΥ {{ $business->tax_office }}@endif<br>
                {{ $business->address }}, {{ $business->postal_code }} {{ $business->city }}<br>
                {{ $business->contact_person }} · {{ $business->mobile }}
            @else
                {{ $quote->user?->name }}
            @endif
        </div>
    </td>
    <td style="padding-left: 3mm">
        <div class="box">
            <div class="label">Καμπάνια</div>
            @if ($quote->starts_on)Έναρξη: <strong>{{ $quote->starts_on->format('d/m/Y') }}</strong><br>@endif
            Διάρκεια: <strong>{{ $quote->days }} {{ $quote->days === 1 ? 'ημέρα' : 'ημέρες' }}</strong><br>
            Ημερομηνία: {{ $quote->created_at->format('d/m/Y') }}<br>
            Ισχύει έως: <strong>{{ $quote->valid_until->format('d/m/Y') }}</strong>
        </div>
    </td>
</tr></table>

<table class="items">
    <thead><tr><th>Περιγραφή</th><th class="num">Τιμή μονάδας</th><th class="num">Ποσότητα</th><th class="num">Ποσό</th></tr></thead>
    <tbody>
    @foreach ($quote->groupedItems() as $group => $lines)
        <tr class="group"><td colspan="4">{{ $group }}</td></tr>
        @foreach ($lines as $line)
            <tr>
                <td>{{ $line['label'] }}
                    @if ($line['detail'])<div class="detail">{{ $line['detail'] }}</div>@endif
                    @foreach ($line['includes'] ?? [] as $inc)<div class="detail">· {{ $inc }}</div>@endforeach
                </td>
                <td class="num">{{ $m($line['unit_price']) }}</td>
                <td class="num">{{ $line['quantity'] }}</td>
                <td class="num">{{ $m($line['amount']) }}</td>
            </tr>
        @endforeach
    @endforeach
    </tbody>
</table>

<table class="totals">
    <tr><td>Σύνολο χωρίς ΦΠΑ</td><td class="num" style="text-align:right">{{ $quote->money('subtotal') }}</td></tr>
    <tr><td>ΦΠΑ {{ rtrim(rtrim((string) $quote->vat_rate, '0'), '.') }}%</td><td style="text-align:right">{{ $quote->money('vat') }}</td></tr>
    <tr class="grand"><td>Τελικό ποσό</td><td style="text-align:right">{{ $quote->money('total') }}</td></tr>
</table>

<div class="notes">
    <strong>Όροι</strong>
    <ul>
        <li>Οι τιμές ισχύουν έως {{ $quote->valid_until->format('d/m/Y') }}. Μετά, η προσφορά ανανεώνεται με τις τρέχουσες τιμές.</li>
        <li>Οι ακριβείς ώρες μετάδοσης ορίζονται από το πρόγραμμα του σταθμού, μέσα στις ζώνες που συμφωνούνται, εκτός αν έχουν επιλεγεί συγκεκριμένες ώρες.</li>
        <li>Η καμπάνια ξεκινά μετά την επιβεβαίωση του προγράμματος, την παράδοση ή έγκριση του υλικού και την εξόφληση.</li>
        <li>Το υλικό πρέπει να συμμορφώνεται με τη νομοθεσία για τη διαφήμιση και τους όρους χρήσης μας.</li>
    </ul>
</div>
</body>
</html>
