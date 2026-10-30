<!doctype html>
<html lang="el">
<head>
<meta charset="utf-8">
<title>{{ $quote->number }}</title>
<style>
    @include('pdf.designs._fonts')
    @page { margin: 18mm 20mm 22mm 20mm; }
    body { font-size: 9.2pt; color: #22263a; line-height: 1.55; }
    .pink { color: #c80c92; }
    .muted { color: #8b8fa3; }
    .caps { font-size: 6.8pt; font-weight: 700; letter-spacing: 1.8px;  color: #8b8fa3; }

    table.top { width: 100%; border-collapse: collapse; }
    table.top td { vertical-align: bottom; padding-bottom: 3mm; border-bottom: 0.3mm solid #22263a; }
    table.top img { height: 11mm; }
    table.top .r { text-align: right; font-size: 7.6pt; color: #8b8fa3; line-height: 1.5; }
    table.top .r strong { color: #22263a; font-weight: 700; }

    .lead { margin-top: 13mm; }
    .title { font-size: 34pt; font-weight: 800; letter-spacing: -1.2px; line-height: 1.02; color: #121a33; margin-top: 2.5mm; }
    .title .dot { color: #c80c92; }
    .for { font-size: 13pt; color: #5a5f78; margin-top: 3.5mm; }
    .for strong { color: #121a33; font-weight: 700; }

    table.facts { width: 100%; border-collapse: collapse; margin-top: 11mm; }
    table.facts td { vertical-align: top; padding: 0 6mm 0 4mm; border-left: 0.3mm solid #dfe1ea; width: 25%; }
    table.facts td:first-child { border-left: 0.9mm solid #c80c92; }
    table.facts .v { font-size: 17pt; font-weight: 800; color: #121a33; letter-spacing: -.4px; margin-top: 1.5mm; line-height: 1.1; }
    table.facts .s { font-size: 7.6pt; color: #8b8fa3; }

    .letter { margin-top: 10mm; width: 128mm; font-size: 10.2pt; line-height: 1.7; color: #3a3f58; }

    .sec { margin-top: 11mm; page-break-inside: avoid; }
    .sec .no { font-size: 8pt; font-weight: 800; color: #c80c92; letter-spacing: 1.5px; }
    .sec h2 { font-size: 15pt; font-weight: 800; color: #121a33; margin: 1mm 0 4mm; letter-spacing: -.3px; }

    /* Πίνακας: κάθε μέσο μια «εγγραφή» με λεπτές γραμμές */
    table.items { width: 100%; border-collapse: collapse; }
    table.items th { text-align: left; padding: 0 0 2mm; border-bottom: 0.3mm solid #22263a; }
    table.items th.r, table.items td.r { text-align: right; }
    table.items td { padding: 3.6mm 0 3.2mm; border-bottom: 0.25mm solid #e7e8ef; vertical-align: top; }
    table.items tr.grp td { page-break-inside: avoid; }
    table.items .lg { width: 21mm; padding-right: 3mm; }
    table.items .lg img { max-width: 18mm; max-height: 11mm; }
    table.items .lg .sw { width: 9mm; height: 9mm; border-radius: 4.5mm; }
    table.items .t { font-size: 11pt; font-weight: 800; color: #121a33; }
    table.items .sub { font-size: 7.8pt; color: #8b8fa3; }
    table.items .ln { font-size: 8.4pt; color: #4a4f68; margin-top: 1.6mm; }
    table.items .ln .d { color: #9a9db0; font-size: 7.4pt; }
    table.items .amt { font-size: 11pt; font-weight: 700; color: #121a33; white-space: nowrap; }
    table.items .qty { font-size: 7.6pt; color: #9a9db0; white-space: nowrap; }
    .tags { margin-top: 1.4mm; font-size: 7.2pt; color: #a10a76; }

    table.totals { width: 82mm; margin-left: auto; border-collapse: collapse; margin-top: 6mm; page-break-inside: avoid; }
    table.totals td { padding: 1.4mm 0; color: #5a5f78; }
    table.totals td.r { text-align: right; }
    table.totals tr.g td { border-top: 0.3mm solid #22263a; padding-top: 3.5mm; color: #121a33; }
    table.totals tr.g .l { font-size: 9pt; font-weight: 700; }
    table.totals tr.g .r { font-size: 21pt; font-weight: 800; letter-spacing: -.6px; }
    .underline { height: 1.2mm; width: 30mm; background: #c80c92; margin-left: auto; margin-top: 1mm; }

    table.steps { width: 100%; border-collapse: collapse; }
    table.steps td { vertical-align: top; padding-right: 6mm; width: 30%; font-size: 8.6pt; color: #4a4f68; }
    table.steps .k { font-size: 22pt; font-weight: 800; color: #e7e8ef; line-height: 1; }
    table.steps strong { display: block; font-size: 10pt; color: #121a33; margin: 1.5mm 0 .8mm; }
    table.steps td.qr { width: 24mm; padding-right: 0; text-align: center; }
    table.steps td.qr img { width: 24mm; height: 24mm; }
    table.steps td.qr div { font-size: 6.6pt; color: #8b8fa3; margin-top: 1mm; }

    table.sign { width: 100%; border-collapse: collapse; margin-top: 12mm; page-break-inside: avoid; }
    table.sign td { vertical-align: bottom; }
    .who { font-size: 12pt; font-weight: 800; color: #121a33; }
    .terms { margin-top: 9mm; padding-top: 3mm; border-top: 0.25mm solid #e7e8ef; font-size: 6.9pt; color: #9a9db0; line-height: 1.5; }

    footer { position: fixed; bottom: -14mm; left: 0; right: 0; font-size: 6.8pt; color: #9a9db0; }
    footer table { width: 100%; border-collapse: collapse; }
    footer .pg:after { content: counter(page); }
</style>
</head>
<body>
<footer><table><tr>
    <td>{{ $company['company_name'] }} · {{ collect([$company['phone'], $company['email']])->filter()->implode(' · ') }}</td>
    <td style="text-align:right">{{ $quote->number }} · <span class="pg"></span></td>
</tr></table></footer>

<table class="top"><tr>
    <td>@if ($logo)<img src="{{ $logo }}" alt="">@else<strong style="font-size:14pt">{{ $company['company_name'] }}</strong>@endif</td>
    <td class="r"><strong>Πρόταση {{ $quote->number }}</strong><br>{{ $quote->created_at->locale('el')->isoFormat('D MMMM YYYY') }}</td>
</tr></table>

<div class="lead">
    <div class="caps">{{ $up('Πρόταση προβολής') }}</div>
    <div class="title">{{ $quote->title ?: 'Η καμπάνια σας' }}<span class="dot">.</span></div>
    @if ($customer)<div class="for">Ετοιμάστηκε για <strong>{{ $customer }}</strong></div>@endif
</div>

<table class="facts"><tr>
    <td><div class="caps">{{ $up('Έναρξη') }}</div><div class="v">{{ $quote->starts_on?->format('d/m/Y') ?? 'Κατόπιν συνεννόησης' }}</div></td>
    <td><div class="caps">{{ $up('Διάρκεια') }}</div><div class="v">{{ $quote->days }} {{ $quote->days === 1 ? 'ημέρα' : 'ημέρες' }}</div></td>
    <td><div class="caps">{{ $up('Μέσα') }}</div><div class="v">{{ count($strip) ?: count($blocks) }}</div><div class="s">{{ $spots ? number_format($spots, 0, ',', '.').' μεταδόσεις σποτ' : '' }}</div></td>
    <td><div class="caps">{{ $up('Επένδυση') }}</div><div class="v pink">{{ $quote->money('total') }}</div><div class="s">με ΦΠΑ {{ $vatLabel }}%</div></td>
</tr></table>

<div class="letter">{!! nl2br(e(preg_replace("/\n{2,}/", "\n", $intro))) !!}</div>

<div class="sec">
    <div class="no">01</div>
    <h2>Η καμπάνια</h2>
</div>
<table class="items">
    <thead><tr>
        <th colspan="2" class="caps">{{ $up('Μέσο') }}</th>
        <th class="caps r">{{ $up('Ποσότητα') }}</th>
        <th class="caps r">{{ $up('Ποσό') }}</th>
    </tr></thead>
    @foreach ($blocks as $b)
        <tr class="grp">
            <td class="lg">
                @if ($b['logo'])<img src="{{ $b['logo'] }}" alt="">@else<div class="sw" style="background: {{ $b['color'] ?: ($b['kind'] === 'other' ? '#22263a' : '#c80c92') }}"></div>@endif
            </td>
            <td>
                <div class="t">{{ $b['kind'] === 'other' && $b['title'] === 'Λοιπά' ? 'Δημιουργικό & λοιπά' : $b['title'] }}</div>
                @if ($b['subtitle'])<div class="sub">{{ $b['subtitle'] }}</div>@endif
                @foreach ($b['lines'] as $line)
                    <div class="ln">{{ $line['label'] === $b['title'] ? ($line['type'] === 'radio' ? 'Διαφημιστικά σποτ' : 'Προβολή') : $line['label'] }}@if ($line['detail']) <span class="d">· {{ $line['detail'] }}</span>@endif</div>
                @endforeach
                @if ($b['chips'])<div class="tags">{{ implode('  ·  ', $b['chips']) }}</div>@endif
            </td>
            <td class="r">
                <div style="height: 6.3mm"></div>
                @foreach ($b['lines'] as $line)<div class="qty" style="margin-top:1.6mm">{{ $line['quantity'] ?? 1 }} × {{ $m($line['unit_price'] ?? $line['amount']) }}</div>@endforeach
            </td>
            <td class="r"><div class="amt">{{ $m($b['total']) }}</div></td>
        </tr>
    @endforeach
</table>

<table class="totals">
    @if ($discounts)
        <tr><td>Αξία μέσων</td><td class="r">{{ $m($beforeDiscount) }}</td></tr>
        @foreach ($discounts as $d)<tr><td class="pink"><strong>{{ $d['label'] }}</strong></td><td class="r pink"><strong>{{ $m($d['amount']) }}</strong></td></tr>@endforeach
    @endif
    <tr><td>Σύνολο χωρίς ΦΠΑ</td><td class="r">{{ $quote->money('subtotal') }}</td></tr>
    <tr><td>ΦΠΑ {{ $vatLabel }}%</td><td class="r">{{ $quote->money('vat') }}</td></tr>
    <tr class="g"><td class="l">Τελικό ποσό</td><td class="r">{{ $quote->money('total') }}</td></tr>
    <tr><td></td><td><div class="underline"></div></td></tr>
</table>

<div class="sec">
    <div class="no">02</div>
    <h2>Τα επόμενα βήματα</h2>
    <table class="steps"><tr>
        <td><div class="k">{{ $up('01') }}</div><strong>Εγκρίνετε</strong>Με ένα κλικ στο Studio σας, ή απαντώντας σε αυτή την πρόταση.</td>
        <td><div class="k">{{ $up('02') }}</div><strong>Υλικό & ώρες</strong>Μας στέλνετε σποτ και λογότυπο, ή τα δημιουργούμε εμείς.</td>
        <td><div class="k">{{ $up('03') }}</div><strong>Στον αέρα</strong>Με την εξόφληση, η καμπάνια ξεκινά την ημέρα που συμφωνήσαμε.</td>
        @if ($qr)<td class="qr"><img src="{{ $qr }}" alt=""><div>Η πρόταση online</div></td>@endif
    </tr></table>
</div>

<table class="sign"><tr>
    <td>
        <div class="muted" style="font-size:8.6pt">Με εκτίμηση,</div>
        <div class="who">{{ $by?->name ?? $company['company_name'] }}</div>
        <div class="muted" style="font-size:8.2pt">{{ collect([$by?->email ?? $company['email'], $company['phone']])->filter()->implode(' · ') }}</div>
    </td>
    <td style="text-align:right" class="muted"><span style="font-size:8pt">Ισχύει έως</span><br><strong style="color:#121a33; font-size:11pt">{{ $quote->valid_until->format('d/m/Y') }}</strong></td>
</tr></table>

<div class="terms">
    Οι τιμές ισχύουν έως {{ $quote->valid_until->format('d/m/Y') }}· μετά η πρόταση ανανεώνεται με τις τρέχουσες τιμές. Οι ακριβείς ώρες μετάδοσης ορίζονται από το πρόγραμμα του σταθμού μέσα στις ζώνες που συμφωνούνται, εκτός αν έχουν επιλεγεί συγκεκριμένες ώρες. Η καμπάνια ξεκινά μετά την επιβεβαίωση του προγράμματος, την έγκριση του υλικού και την εξόφληση.@if($company['vat']) {{ $company['company_name'] }} · ΑΦΜ {{ $company['vat'] }}@endif
</div>
</body>
</html>
