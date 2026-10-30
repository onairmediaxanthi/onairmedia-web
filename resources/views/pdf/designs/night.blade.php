<!doctype html>
<html lang="el">
<head>
<meta charset="utf-8">
<title>{{ $quote->number }}</title>
<style>
    @include('pdf.designs._fonts')
    @page { margin: 16mm 16mm 20mm 16mm; }
    body { font-size: 9.2pt; color: #dfe3f0; line-height: 1.5; }
    /* Σκούρο φόντο σε κάθε σελίδα, από άκρη σε άκρη */
    .bg { position: fixed; top: -16mm; left: -16mm; width: 210mm; height: 297mm; background: #0f1630; z-index: -10; }
    .bg .g1 { position: absolute; width: 150mm; height: 150mm; border-radius: 50%; background: #1b2550; right: -70mm; top: -60mm; opacity: .7; }
    .bg .g2 { position: absolute; width: 110mm; height: 110mm; border-radius: 50%; background: #2a1440; left: -60mm; bottom: -50mm; opacity: .6; }
    .gold { color: #ffb627; }
    .pink { color: #ff4fb8; }
    .muted { color: #8f98b8; }
    .caps { font-size: 6.8pt; font-weight: 700; letter-spacing: 1.8px;  color: #8f98b8; }

    table.top { width: 100%; border-collapse: collapse; }
    table.top td { vertical-align: middle; }
    table.top img { height: 13mm; }
    table.top .r { text-align: right; font-size: 7.8pt; color: #8f98b8; }
    table.top .r strong { display: block; color: #fff; font-size: 10.5pt; }

    .hero { margin-top: 16mm; }
    .eq { height: 9mm; }
    .eq span { display: inline-block; width: 1.3mm; margin-right: 1mm; border-radius: .65mm; background: #ffb627; vertical-align: bottom; }
    .title { font-size: 32pt; font-weight: 800; color: #fff; letter-spacing: -1px; line-height: 1.04; margin-top: 4mm; }
    .for { font-size: 12.5pt; color: #b9c1dc; margin-top: 3mm; }
    .for strong { color: #fff; }

    table.facts { width: 100%; border-collapse: separate; border-spacing: 2.5mm 0; margin: 11mm -2.5mm 0; }
    table.facts td { background: #18214a; border-radius: 3.5mm; padding: 4mm 4.5mm; vertical-align: top; width: 25%; }
    table.facts .v { font-size: 15pt; font-weight: 800; color: #fff; margin-top: 1.4mm; letter-spacing: -.3px; }
    table.facts td.hl { background: #ffb627; }
    table.facts td.hl .caps { color: #6b4a00; }
    table.facts td.hl .v { color: #0f1630; }
    table.facts .s { font-size: 7.2pt; color: #8f98b8; }
    table.facts td.hl .s { color: #6b4a00; }

    .intro { margin-top: 9mm; font-size: 9.8pt; color: #c6cce2; line-height: 1.65; width: 150mm; }

    h2 { font-size: 13pt; font-weight: 800; color: #fff; margin: 9mm 0 3.5mm; }
    h2 .n { color: #ffb627; font-size: 9pt; letter-spacing: 1.5px; margin-right: 2.5mm; }

    .card { background: #151d42; border-radius: 4mm; margin-bottom: 3mm; page-break-inside: avoid; border-top: 1.2mm solid #ffb627; }
    .card .in { padding: 4mm 5mm; }
    .card table.h { width: 100%; border-collapse: collapse; }
    .card .lg { width: 22mm; vertical-align: middle; }
    .card .lg .w { background: #fff; border-radius: 3mm; width: 17mm; height: 13mm; text-align: center; }
    .card .lg .w img { max-width: 14mm; max-height: 10mm; margin-top: 1.5mm; }
    .card .lg table.mono { border-collapse: collapse; }
    .card .lg table.mono td { width: 13mm; height: 13mm; border-radius: 6.5mm; text-align: center; vertical-align: middle; color: #0f1630; font-weight: 800; font-size: 13pt; padding: 0; }
    .card .t { font-size: 12pt; font-weight: 800; color: #fff; }
    .card .sub { font-size: 7.9pt; color: #8f98b8; }
    .card .slogan { font-size: 8.4pt; color: #c6cce2; margin-top: .5mm; }
    .card .amt { text-align: right; white-space: nowrap; vertical-align: top; font-size: 13pt; font-weight: 800; color: #ffb627; }
    .chip { display: inline-block; background: #222c5e; color: #cfd6f2; border-radius: 3mm; padding: .5mm 2.2mm; font-size: 6.7pt; margin: 1.3mm 1mm 0 0; }
    table.lines { width: 100%; border-collapse: collapse; margin-top: 2.5mm; }
    table.lines td { padding: 1.5mm 0; border-top: 0.25mm solid #26306a; font-size: 8.4pt; color: #dfe3f0; vertical-align: top; }
    table.lines .q { text-align: right; color: #8f98b8; padding: 1.5mm 4mm; white-space: nowrap; font-size: 7.8pt; }
    table.lines .a { text-align: right; white-space: nowrap; font-weight: 600; color: #fff; }
    .detail { font-size: 7.3pt; color: #8f98b8; }

    table.sum { width: 100%; border-collapse: collapse; margin-top: 3mm; page-break-inside: avoid; }
    table.sum td { vertical-align: bottom; }
    .calc { width: 100%; border-collapse: collapse; }
    .calc td { padding: 1.3mm 0; color: #b9c1dc; }
    .calc td.v { text-align: right; }
    .total { background: #ffb627; border-radius: 4mm; padding: 5mm 6mm; color: #0f1630; }
    .total .l { font-size: 7.4pt; font-weight: 800; letter-spacing: 1.8px; color: #6b4a00; }
    .total .v { font-size: 23pt; font-weight: 800; letter-spacing: -.6px; color: #0f1630; margin-top: .5mm; }
    .total .s { font-size: 7.4pt; color: #6b4a00; }

    table.steps { width: 100%; border-collapse: separate; border-spacing: 2.5mm 0; margin: 0 -2.5mm; }
    table.steps td { background: #151d42; border-radius: 3.5mm; padding: 4mm; vertical-align: top; width: 26%; font-size: 8.3pt; color: #b9c1dc; }
    table.steps .no { font-size: 17pt; font-weight: 800; color: #ffb627; line-height: 1; }
    table.steps strong { display: block; color: #fff; font-size: 9.6pt; margin: 1.5mm 0 .8mm; }
    table.steps td.qr { background: #fff; text-align: center; width: 22%; }
    table.steps td.qr img { width: 27mm; height: 27mm; }
    table.steps td.qr div { color: #0f1630; font-size: 7.4pt; font-weight: 700; margin-top: 1mm; }

    .sign { margin-top: 8mm; page-break-inside: avoid; }
    .who { font-size: 12pt; font-weight: 800; color: #fff; }
    .terms { margin-top: 6mm; font-size: 6.8pt; color: #6f789a; line-height: 1.5; }

    footer { position: fixed; bottom: -13mm; left: 0; right: 0; font-size: 6.9pt; color: #6f789a; }
    footer table { width: 100%; border-collapse: collapse; border-top: 0.25mm solid #26306a; }
    footer td { padding-top: 2.2mm; }
    footer .pg:after { content: counter(page); }
</style>
</head>
<body>
<div class="bg"><div class="g1"></div><div class="g2"></div></div>
<footer><table><tr>
    <td><strong class="gold">{{ $company['company_name'] }}</strong> · {{ collect([$company['phone'], $company['email']])->filter()->implode(' · ') }}</td>
    <td style="text-align:right">{{ $quote->number }} · σελ. <span class="pg"></span></td>
</tr></table></footer>

<table class="top"><tr>
    <td>@if ($logoDark)<img src="{{ $logoDark }}" alt="">@else<strong style="font-size:15pt; color:#fff">{{ $company['company_name'] }}</strong>@endif</td>
    <td class="r"><strong>{{ $quote->number }}</strong>{{ $quote->created_at->format('d/m/Y') }}</td>
</tr></table>

<div class="hero">
    {{-- «Ισοσταθμιστής»: η ταυτότητα του ραδιοφώνου, με απλά σχήματα --}}
    <div class="eq">@foreach ([3, 6, 9, 5, 8, 4, 7, 9, 6, 3, 5, 8, 6, 4, 2] as $h)<span style="height: {{ $h }}mm; opacity: {{ 0.45 + $h / 18 }}"></span>@endforeach</div>
    <div class="caps" style="margin-top:4mm">{{ $up('Πρόταση προβολής') }}</div>
    <div class="title">{{ $quote->title ?: 'Η καμπάνια σας' }}</div>
    @if ($customer)<div class="for">για <strong>{{ $customer }}</strong></div>@endif
</div>

<table class="facts"><tr>
    <td><div class="caps">{{ $up('Έναρξη') }}</div><div class="v">{{ $quote->starts_on?->format('d/m/Y') ?? 'Κατόπιν' }}</div><div class="s">{{ $quote->starts_on ? 'ημερομηνία έναρξης' : 'συνεννόησης' }}</div></td>
    <td><div class="caps">{{ $up('Διάρκεια') }}</div><div class="v">{{ $quote->days }} {{ $quote->days === 1 ? 'ημέρα' : 'ημέρες' }}</div><div class="s">συνεχής προβολή</div></td>
    <td><div class="caps">{{ $up($spots ? 'Μεταδόσεις' : 'Μέσα') }}</div><div class="v">{{ $spots ? number_format($spots, 0, ',', '.') : count($strip) }}</div><div class="s">{{ $spots ? 'σποτ στον αέρα' : 'στην πρόταση' }}</div></td>
    <td class="hl"><div class="caps">{{ $up('Επένδυση') }}</div><div class="v">{{ $quote->money('total') }}</div><div class="s">με ΦΠΑ {{ $vatLabel }}%</div></td>
</tr></table>

<div class="intro">{!! nl2br(e(preg_replace("/\n{2,}/", "\n", $intro))) !!}</div>

@foreach ($media as $b)
    @php($c = $b['color'] ?: '#ffb627')
    @if ($loop->first)<div style="page-break-inside: avoid"><h2><span class="n">01</span>Τα μέσα σας</h2>@endif
    <div class="card" style="border-top-color: {{ $c }}">
        <div class="in">
            <table class="h"><tr>
                <td class="lg">
                    @if ($b['logo'])<div class="w"><img src="{{ $b['logo'] }}" alt=""></div>
                    @else<table class="mono"><tr><td style="background: {{ $c }}">{{ mb_strtoupper(mb_substr($b['title'], 0, 1)) }}</td></tr></table>@endif
                </td>
                <td>
                    <div class="t">{{ $b['title'] }}</div>
                    @if ($b['subtitle'])<div class="sub">{{ $b['subtitle'] }}</div>@endif
                    @if ($b['text'])<div class="slogan">{{ $b['text'] }}</div>@endif
                    @if ($b['chips'])<div>@foreach ($b['chips'] as $ch)<span class="chip">{{ $ch }}</span>@endforeach</div>@endif
                </td>
                <td class="amt">{{ $m($b['total']) }}</td>
            </tr></table>
            <table class="lines">
                @foreach ($b['lines'] as $line)
                    <tr>
                        <td>{{ $line['label'] === $b['title'] ? ($line['type'] === 'radio' ? 'Διαφημιστικά σποτ' : 'Προβολή') : $line['label'] }}@if ($line['detail'])<div class="detail">{{ $line['detail'] }}</div>@endif</td>
                        <td class="q">{{ $line['quantity'] }} × {{ $m($line['unit_price']) }}</td>
                        <td class="a">{{ $m($line['amount']) }}</td>
                    </tr>
                @endforeach
            </table>
        </div>
    </div>
    @if ($loop->first)</div>@endif
@endforeach

@if ($extras)
    <div class="card" style="border-top-color:#8f98b8">
        <div class="in">
            <div class="t" style="font-size:11pt">Δημιουργικό & λοιπά</div>
            <table class="lines">
                @foreach ($extras as $b)
                    @foreach ($b['lines'] as $line)
                        <tr>
                            <td>{{ $line['label'] }}@if ($line['detail'])<div class="detail">{{ $line['detail'] }}</div>@endif
                                @foreach ($line['includes'] ?? [] as $inc)<div class="detail">· {{ $inc }}</div>@endforeach</td>
                            <td class="q">{{ $line['quantity'] ?? 1 }} × {{ $m($line['unit_price'] ?? $line['amount']) }}</td>
                            <td class="a">{{ $m($line['amount']) }}</td>
                        </tr>
                    @endforeach
                @endforeach
            </table>
        </div>
    </div>
@endif

<table class="sum"><tr>
    <td style="width:50%; padding-right:6mm">
        <table class="calc">
            @if ($discounts)
                <tr><td>Αξία μέσων</td><td class="v">{{ $m($beforeDiscount) }}</td></tr>
                @foreach ($discounts as $d)<tr><td class="pink"><strong>{{ $d['label'] }}</strong></td><td class="v pink"><strong>{{ $m($d['amount']) }}</strong></td></tr>@endforeach
            @endif
            <tr><td>Σύνολο χωρίς ΦΠΑ</td><td class="v">{{ $quote->money('subtotal') }}</td></tr>
            <tr><td>ΦΠΑ {{ $vatLabel }}%</td><td class="v">{{ $quote->money('vat') }}</td></tr>
        </table>
    </td>
    <td style="width:50%">
        <div class="total">
            <div class="l">{{ $up('ΤΕΛΙΚΟ ΠΟΣΟ') }}</div>
            <div class="v">{{ $quote->money('total') }}</div>
            <div class="s">Οι τιμές ισχύουν έως {{ $quote->valid_until->format('d/m/Y') }}</div>
        </div>
    </td>
</tr></table>

<div style="page-break-inside: avoid">
    <h2><span class="n">02</span>Πώς προχωράμε</h2>
    <table class="steps"><tr>
        <td><div class="no">01</div><strong>Εγκρίνετε</strong>Με ένα κλικ στο Studio σας ή απαντώντας σε αυτή την πρόταση.</td>
        <td><div class="no">02</div><strong>Υλικό & ώρες</strong>Μας στέλνετε σποτ και λογότυπο ή τα φτιάχνουμε εμείς.</td>
        <td><div class="no">03</div><strong>Στον αέρα</strong>Με την εξόφληση, η καμπάνια ξεκινά όπως συμφωνήσαμε.</td>
        @if ($qr)<td class="qr"><img src="{{ $qr }}" alt=""><div>Δείτε & εγκρίνετε online</div></td>@endif
    </tr></table>

    <div class="sign">
        <div class="muted" style="font-size:8.4pt">Για οποιαδήποτε απορία, είμαστε εδώ</div>
        <div class="who">{{ $by?->name ?? $company['company_name'] }}</div>
        <div class="muted" style="font-size:8.2pt">{{ collect([$by?->email ?? $company['email'], $company['phone']])->filter()->implode(' · ') }}</div>
    </div>
    <div class="terms">
        Οι τιμές ισχύουν έως {{ $quote->valid_until->format('d/m/Y') }}· μετά η πρόταση ανανεώνεται με τις τρέχουσες τιμές. Οι ακριβείς ώρες μετάδοσης ορίζονται από το πρόγραμμα του σταθμού μέσα στις ζώνες που συμφωνούνται, εκτός αν έχουν επιλεγεί συγκεκριμένες ώρες. Η καμπάνια ξεκινά μετά την επιβεβαίωση του προγράμματος, την έγκριση του υλικού και την εξόφληση.@if($company['vat']) {{ $company['company_name'] }} · ΑΦΜ {{ $company['vat'] }}@endif
    </div>
</div>
</body>
</html>
