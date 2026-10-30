<!doctype html>
<html lang="el">
<head>
<meta charset="utf-8">
<title>{{ $quote->number }}</title>
<style>
    @include('pdf.designs._fonts')
    @page { margin: 16mm 16mm 20mm 16mm; }
    body { font-size: 9.2pt; color: #1d2340; line-height: 1.5; }
    .pink { color: #c80c92; }

    /* Εξώφυλλο: ροζ ζώνη από άκρη σε άκρη, με «ηχητικά κύματα» */
    .cover { position: absolute; top: -16mm; left: -16mm; width: 210mm; height: 108mm; background: #c80c92; overflow: hidden; }
    .wave { position: absolute; border-radius: 50%; border: 1.1mm solid #ffffff; }
    .w1 { width: 120mm; height: 120mm; right: -42mm; top: -38mm; opacity: .10; }
    .w2 { width: 170mm; height: 170mm; right: -67mm; top: -63mm; opacity: .07; }
    .w3 { width: 220mm; height: 220mm; right: -92mm; top: -88mm; opacity: .05; }
    .dot { position: absolute; width: 58mm; height: 58mm; border-radius: 50%; background: #9b0872; right: -12mm; top: -8mm; opacity: .55; }
    .glow { position: absolute; width: 90mm; height: 90mm; border-radius: 50%; background: #e0479f; left: -40mm; bottom: -55mm; opacity: .45; }

    .head { position: relative; height: 84mm; color: #fff; }
    .head .logo img { height: 15mm; }
    .head .logo .txt { font-size: 15pt; font-weight: 800; color: #fff; letter-spacing: -.3px; }
    .head .meta { position: absolute; right: 0; top: 2mm; text-align: right; font-size: 8pt; color: #ffd6ee; }
    .head .meta strong { display: block; color: #fff; font-size: 10.5pt; font-weight: 700; }
    .eyebrow { margin-top: 11mm; font-size: 8pt; font-weight: 700; letter-spacing: 2.4px; color: #ffd6ee; }
    .title { font-size: 30pt; font-weight: 800; line-height: 1.04; letter-spacing: -.8px; margin-top: 2mm; color: #fff; }
    .for { margin-top: 3mm; font-size: 12.5pt; color: #fff; }
    .for span { font-weight: 700; }
    .tag { position: absolute; left: 0; bottom: 8mm; font-size: 8pt; font-weight: 700; letter-spacing: 1.2px; color: #ffd6ee; }

    /* Κάρτα με τα βασικά, που «πατάει» πάνω στη ζώνη */
    .facts { position: relative; background: #fff; border-radius: 4mm; border: 0.35mm solid #f0dbe8; margin-top: 0; }
    .facts table { width: 100%; border-collapse: collapse; }
    .facts td { padding: 3.6mm 5mm; vertical-align: top; border-left: 0.3mm solid #f1e6ed; width: 25%; }
    .facts td:first-child { border-left: none; }
    .facts .k { font-size: 6.8pt; font-weight: 700; letter-spacing: 1px; color: #9a8a96;  }
    .facts .v { font-size: 14pt; font-weight: 800; color: #1d2340; margin-top: .8mm; }
    .facts .v.big { color: #c80c92; }
    .facts .s { font-size: 7.3pt; color: #8c90a3; }

    .intro { margin: 5.5mm 1mm 0; font-size: 9.4pt; color: #3a4060; line-height: 1.55; }

    h2 { font-size: 12.5pt; font-weight: 800; margin: 6mm 0 2.5mm; letter-spacing: -.2px; }
    h2 .n { color: #c80c92; font-size: 10pt; font-weight: 800; letter-spacing: 1px; margin-right: 2.5mm; }

    /* Λωρίδα λογοτύπων */
    table.wall { width: 100%; border-collapse: separate; border-spacing: 2.5mm 0; margin: 0 -2.5mm; }
    table.wall td { background: #faf5f8; border-radius: 3mm; text-align: center; vertical-align: middle; height: 17mm; padding: 1.8mm; }
    table.wall img { max-height: 10.5mm; max-width: 28mm; }
    table.wall .n { font-size: 6.8pt; color: #7b7f93; margin-top: 1.2mm; }

    table.glance { width: 100%; border-collapse: collapse; }
    table.glance td { padding: 1.8mm 0; border-bottom: 0.25mm solid #f0eaf0; vertical-align: middle; font-size: 9pt; }
    table.glance td.lg { width: 22mm; padding-right: 3mm; }
    table.glance td.lg img { max-height: 9mm; max-width: 19mm; }
    table.glance td.lg .sq { width: 3.5mm; height: 3.5mm; border-radius: 1.75mm; margin-left: 1mm; }
    table.glance .g { color: #8c90a3; font-size: 8pt; margin-left: 1.5mm; }
    table.glance td.a { text-align: right; white-space: nowrap; font-weight: 700; }
    table.glance tr.tot td { border-bottom: none; padding-top: 2.4mm; font-size: 11pt; font-weight: 800; color: #c80c92; }

    /* Κάρτες μέσων */
    .card { border: 0.35mm solid #ece7ef; border-radius: 4mm; margin-bottom: 3.5mm; page-break-inside: avoid; overflow: hidden; }
    .card .bar { height: 1.6mm; }
    .card .in { padding: 4mm 5mm 3.5mm; }
    .card table.h { width: 100%; border-collapse: collapse; }
    .card .lg { width: 24mm; vertical-align: middle; }
    .card .lg img { max-width: 19mm; max-height: 14mm; }
    .card .lg table.mono { border-collapse: collapse; }
    .card .lg table.mono td { width: 15mm; height: 15mm; border-radius: 7.5mm; color: #fff; font-weight: 800; font-size: 14pt; text-align: center; vertical-align: middle; padding: 0; }
    .card .t { font-size: 12pt; font-weight: 800; color: #1d2340; }
    .card .sub { font-size: 8pt; color: #7b7f93; }
    .card .slogan { font-size: 8.6pt; color: #4a5070; margin-top: .6mm; }
    .card .amt { text-align: right; vertical-align: top; white-space: nowrap; }
    .card .amt .v { font-size: 13pt; font-weight: 800; }
    .card .amt .l { font-size: 6.5pt; color: #9a9db0; letter-spacing: .6px;  }
    .chip { display: inline-block; background: #fbeaf4; color: #a10a76; border-radius: 3mm; padding: .5mm 2.3mm; font-size: 6.8pt; font-weight: 600; margin: 1.4mm 1mm 0 0; }
    table.lines { width: 100%; border-collapse: collapse; margin-top: 3mm; }
    table.lines td { padding: 1.6mm 0; border-top: 0.25mm dotted #dcd6e0; vertical-align: top; font-size: 8.6pt; }
    table.lines .q { text-align: right; color: #8c90a3; white-space: nowrap; padding: 1.6mm 4mm; font-size: 8pt; }
    table.lines .a { text-align: right; white-space: nowrap; font-weight: 600; }
    .detail { font-size: 7.4pt; color: #8c90a3; }

    /* Σύνολα */
    table.sum { width: 100%; border-collapse: collapse; margin-top: 2mm; page-break-inside: avoid; }
    table.sum td { vertical-align: bottom; }
    .calc { width: 100%; border-collapse: collapse; }
    .calc td { padding: 1.3mm 0; color: #4a5070; }
    .calc td.v { text-align: right; }
    .total { background: #1d2340; color: #fff; border-radius: 4mm; padding: 5mm 6mm; }
    .total .l { font-size: 7.5pt; letter-spacing: 1.6px; color: #b8bdd4; font-weight: 700; }
    .total .v { font-size: 22pt; font-weight: 800; color: #fff; margin-top: .5mm; letter-spacing: -.5px; }
    .total .s { font-size: 7.5pt; color: #b8bdd4; }

    /* Επόμενα βήματα */
    table.steps { width: 100%; border-collapse: separate; border-spacing: 2.5mm 0; margin: 0 -2.5mm; }
    table.steps td { background: #faf5f8; border-radius: 3.5mm; padding: 4mm; vertical-align: top; width: 26%; font-size: 8.4pt; color: #4a5070; }
    table.steps .no { font-size: 18pt; font-weight: 800; color: #c80c92; line-height: 1; }
    table.steps strong { display: block; color: #1d2340; font-size: 9.6pt; margin: 1.5mm 0 1mm; }
    table.steps td.qr { background: #c80c92; color: #fff; text-align: center; width: 22%; }
    table.steps td.qr img { width: 27mm; height: 27mm; background: #fff; border-radius: 2mm; padding: 1.2mm; }
    table.steps td.qr div { font-size: 7.6pt; margin-top: 1.5mm; color: #fff; font-weight: 600; }

    .sign { margin-top: 7mm; page-break-inside: avoid; }
    .sign table { width: 100%; border-collapse: collapse; }
    .sign .who { font-size: 11pt; font-weight: 800; }
    .sign .role { color: #7b7f93; font-size: 8.4pt; }
    .terms { margin-top: 6mm; font-size: 6.9pt; color: #9a9db0; line-height: 1.45; }

    footer { position: fixed; bottom: -13mm; left: 0; right: 0; height: 8mm; font-size: 7pt; color: #9a9db0; }
    footer table { width: 100%; border-collapse: collapse; border-top: 0.3mm solid #eee; }
    footer td { padding-top: 2.2mm; }
    footer .pg:after { content: counter(page); }
</style>
</head>
<body>
<footer><table><tr>
    <td><strong class="pink">{{ $company['company_name'] }}</strong> · {{ collect([$company['phone'], $company['email']])->filter()->implode(' · ') }}</td>
    <td style="text-align:right">{{ $quote->number }} · σελ. <span class="pg"></span></td>
</tr></table></footer>

<div class="cover">
    <div class="glow"></div><div class="dot"></div>
    <div class="wave w1"></div><div class="wave w2"></div><div class="wave w3"></div>
</div>

<div class="head">
    <div class="logo">
        @if ($logoDark)<img src="{{ $logoDark }}" alt="">@else<span class="txt">{{ $company['company_name'] }}</span>@endif
    </div>
    <div class="meta"><strong>{{ $quote->number }}</strong>{{ $quote->created_at->format('d/m/Y') }}</div>

    <div class="eyebrow">ΠΡΟΤΑΣΗ ΠΡΟΒΟΛΗΣ</div>
    <div class="title">{{ $quote->title ?: 'Η καμπάνια σας' }}</div>
    @if ($customer)<div class="for">για <span>{{ $customer }}</span></div>@endif
    <div class="tag">Ακούγεσαι. Φαίνεσαι. Μετράς.</div>
</div>

<div class="facts"><table><tr>
    <td><div class="k">{{ $up('Έναρξη') }}</div><div class="v">{{ $quote->starts_on?->format('d/m') ?? 'Κατόπιν' }}</div><div class="s">{{ $quote->starts_on?->format('Y') ?? 'συνεννόησης' }}</div></td>
    <td><div class="k">{{ $up('Διάρκεια') }}</div><div class="v">{{ $quote->days }}</div><div class="s">{{ $quote->days === 1 ? 'ημέρα' : 'ημέρες' }}</div></td>
    <td><div class="k">{{ $up($spots ? 'Μεταδόσεις' : 'Μέσα') }}</div><div class="v">{{ $spots ? number_format($spots, 0, ',', '.') : count($strip) }}</div><div class="s">{{ $spots ? 'σποτ στον αέρα' : 'στην πρόταση' }}</div></td>
    <td><div class="k">{{ $up('Επένδυση') }}</div><div class="v big">{{ $quote->money('total') }}</div><div class="s">με ΦΠΑ {{ $vatLabel }}%</div></td>
</tr></table></div>

<div class="intro">{!! nl2br(e(preg_replace("/\n{2,}/", "\n", $intro))) !!}</div>

{{-- Με μια ματιά: όλη η επένδυση στην πρώτη σελίδα --}}
<h2><span class="n">01</span>Τα μέσα σας και η επένδυση</h2>
<table class="glance">
    @foreach ($blocks as $b)
        <tr>
            <td class="lg">@if ($b['logo'])<img src="{{ $b['logo'] }}" alt="">@else<div class="sq" style="background: {{ $b['color'] ?: ($b['kind'] === 'other' ? '#1d2340' : '#c80c92') }}"></div>@endif</td>
            <td><strong>{{ $b['kind'] === 'other' ? ($b['title'] === 'Λοιπά' ? 'Δημιουργικό & λοιπά' : $b['title']) : $b['title'] }}</strong>
                <span class="g">{{ $b['kind'] === 'radio' ? collect($b['lines'])->sum('quantity').' σποτ' : ($b['kind'] === 'other' ? collect($b['lines'])->pluck('label')->implode(', ') : ($b['subtitle'] ?? '')) }}</span></td>
            <td class="a">{{ $m($b['total']) }}</td>
        </tr>
    @endforeach
    @foreach ($discounts as $d)
        <tr><td class="lg"></td><td class="pink"><strong>{{ $d['label'] }}</strong></td><td class="a pink">{{ $m($d['amount']) }}</td></tr>
    @endforeach
    <tr class="tot"><td class="lg"></td><td>Σύνολο με ΦΠΑ {{ $vatLabel }}%</td><td class="a">{{ $quote->money('total') }}</td></tr>
</table>

<div style="page-break-before: always"></div>
@foreach ($media as $b)
    @php($c = $b['color'] ?: '#c80c92')
    @if ($loop->first)<div style="page-break-inside: avoid"><h2 style="margin-top:0"><span class="n">02</span>Η καμπάνια αναλυτικά</h2>@endif
    <div class="card">
        <div class="bar" style="background: {{ $c }}"></div>
        <div class="in">
            <table class="h"><tr>
                <td class="lg">
                    @if ($b['logo'])<img src="{{ $b['logo'] }}" alt="">
                    @else<table class="mono"><tr><td style="background: {{ $c }}">{{ mb_strtoupper(mb_substr($b['title'], 0, 1)) }}</td></tr></table>@endif
                </td>
                <td>
                    <div class="t">{{ $b['title'] }}</div>
                    @if ($b['subtitle'])<div class="sub">{{ $b['subtitle'] }}</div>@endif
                    @if ($b['text'])<div class="slogan">{{ $b['text'] }}</div>@endif
                    @if ($b['chips'])<div>@foreach ($b['chips'] as $ch)<span class="chip">{{ $ch }}</span>@endforeach</div>@endif
                </td>
                <td class="amt"><div class="l">{{ $up('Σύνολο') }}</div><div class="v">{{ $m($b['total']) }}</div></td>
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
    <div class="card">
        <div class="bar" style="background:#1d2340"></div>
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
                @foreach ($discounts as $d)<tr><td class="pink" style="font-weight:700">{{ $d['label'] }}</td><td class="v pink" style="font-weight:700">{{ $m($d['amount']) }}</td></tr>@endforeach
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
    <h2><span class="n">03</span>Πώς προχωράμε</h2>
    <table class="steps"><tr>
        <td><div class="no">01</div><strong>Εγκρίνετε</strong>Με ένα κλικ στο Studio σας ή απαντώντας σε αυτή την πρόταση.</td>
        <td><div class="no">02</div><strong>Υλικό & ώρες</strong>Μας στέλνετε σποτ και λογότυπο ή τα φτιάχνουμε εμείς.</td>
        <td><div class="no">03</div><strong>Στον αέρα</strong>Με την εξόφληση, η καμπάνια ξεκινά όπως συμφωνήσαμε.</td>
        @if ($qr)<td class="qr"><img src="{{ $qr }}" alt=""><div>Δείτε & εγκρίνετε<br>online</div></td>@endif
    </tr></table>

    <div class="sign"><table><tr>
        <td>
            <div class="role">Για οποιαδήποτε απορία, είμαστε εδώ</div>
            <div class="who">{{ $by?->name ?? $company['company_name'] }}</div>
            <div class="role">{{ collect([$by?->email ?? $company['email'], $company['phone']])->filter()->implode(' · ') }}</div>
        </td>
        <td style="text-align:right; vertical-align:bottom">
            @if ($logo)<img src="{{ $logo }}" alt="" style="height:10mm">@endif
        </td>
    </tr></table></div>

    <div class="terms">
        Οι τιμές ισχύουν έως {{ $quote->valid_until->format('d/m/Y') }}· μετά η πρόταση ανανεώνεται με τις τρέχουσες τιμές. Οι ακριβείς ώρες μετάδοσης ορίζονται από το πρόγραμμα του σταθμού μέσα στις ζώνες που συμφωνούνται, εκτός αν έχουν επιλεγεί συγκεκριμένες ώρες. Η καμπάνια ξεκινά μετά την επιβεβαίωση του προγράμματος, την έγκριση του υλικού και την εξόφληση.@if($company['vat']) {{ $company['company_name'] }} · ΑΦΜ {{ $company['vat'] }}@endif
    </div>
</div>
</body>
</html>
