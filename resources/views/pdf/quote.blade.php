@php
    $m = fn ($v) => \App\Services\Quotes\QuoteCalculator::money($v);
    $blocks = \App\Services\Quotes\QuotePresenter::blocks($quote, 'data');
    $strip = \App\Services\Quotes\QuotePresenter::mediaStrip($blocks);
    $intro = \App\Services\Quotes\QuotePresenter::intro($quote);
    $by = $quote->createdBy;
    $customer = $business?->displayName() ?? $quote->user?->name;
    $vatLabel = rtrim(rtrim((string) $quote->vat_rate, '0'), '.');
    $discounts = \App\Services\Quotes\QuotePresenter::discounts($quote);
    $media = array_values(array_filter($blocks, fn ($b) => $b['kind'] !== 'other'));
    $extras = array_values(array_filter($blocks, fn ($b) => $b['kind'] === 'other'));
@endphp
<!doctype html>
<html lang="el">
<head>
<meta charset="utf-8">
<title>{{ $quote->number }}</title>
<style>
    @page { margin: 12mm 15mm 18mm 15mm; }
    * { font-family: 'DejaVu Sans', sans-serif; }
    body { font-size: 9pt; color: #121a33; line-height: 1.45; }
    table.top { width: 100%; border-collapse: collapse; margin-bottom: 5mm; }
    table.top td { vertical-align: middle; }
    table.top img { height: 21mm; }
    table.top .brand { font-size: 22pt; font-weight: bold; color: #c80c92; }
    table.top .right { text-align: right; font-size: 8pt; color: #666; }
    table.top .right strong { display: block; font-size: 11pt; color: #121a33; }
    footer { position: fixed; bottom: -12mm; left: 0; right: 0; font-size: 7pt; color: #888; border-top: 1px solid #e3e3ea; padding-top: 2mm; }
    footer .r { position: absolute; right: 0; top: 2mm; }

    .hero { background: #121a33; color: #fff; border-radius: 4mm; padding: 7mm 8mm; }
    .hero .eyebrow { color: #ffb627; font-size: 8pt; font-weight: bold; letter-spacing: 1.5px; }
    .hero h1 { font-size: 20pt; margin: 1.5mm 0 0; line-height: 1.15; }
    .hero .facts { width: 100%; margin-top: 5mm; border-collapse: collapse; }
    .hero .facts td { color: #fff; padding: 0 4mm 0 0; vertical-align: top; }
    .hero .facts .k { font-size: 6.8pt; color: #a8b0c8; text-transform: uppercase; letter-spacing: .6px; }
    .hero .facts .v { font-size: 10pt; font-weight: bold; }

    .intro { margin: 5mm 1mm 0; font-size: 9.5pt; color: #2a3048; }
    h2 { font-size: 11pt; margin: 5mm 0 2.5mm; color: #c80c92; }

    table.strip { width: 100%; border-collapse: separate; border-spacing: 2mm 0; margin: 0 -2mm; }
    table.strip td { border: 1px solid #e3e3ea; border-radius: 3mm; text-align: center; vertical-align: middle; padding: 2.5mm 2mm; height: 15mm; }
    table.strip img { max-height: 12mm; max-width: 26mm; }
    table.strip .n { font-size: 7pt; color: #555; margin-top: 1.5mm; }
    .textlogo { display: inline-block; font-weight: bold; font-size: 10pt; color: #fff; background: #121a33; border-radius: 2mm; padding: 2mm 3mm; }

    .block { border: 1px solid #e3e3ea; border-left-width: 1.6mm; border-radius: 3mm; padding: 3mm 4mm; margin-bottom: 3mm; page-break-inside: avoid; }
    .block table.head { width: 100%; border-collapse: collapse; }
    .block .logo { width: 17mm; vertical-align: top; }
    .block .logo img { max-width: 15mm; max-height: 12mm; }
    .block .t { font-size: 11pt; font-weight: bold; }
    .block .s { color: #666; font-size: 8pt; }
    .block .slogan { font-style: italic; color: #444; }
    .chip { display: inline-block; background: #f6eef3; color: #8a0b66; border-radius: 3mm; padding: .6mm 2.2mm; font-size: 7pt; margin: 1mm 1mm 0 0; }
    .block .sum { text-align: right; vertical-align: top; white-space: nowrap; font-weight: bold; font-size: 10.5pt; }
    table.lines { width: 100%; border-collapse: collapse; margin-top: 2mm; }
    table.lines td { border-top: 1px solid #efeff3; padding: 1.2mm 0; vertical-align: top; }
    table.lines .num { text-align: right; white-space: nowrap; padding-left: 3mm; }
    .detail { font-size: 7.5pt; color: #777; }

    table.totals { width: 52%; margin-left: 48%; border-collapse: collapse; margin-top: 2mm; }
    table.totals td { padding: 1.4mm 0; }
    table.totals td.v { text-align: right; }
    .grand { background: #c80c92; color: #fff; border-radius: 2.5mm; padding: 3mm 4mm; margin: 2mm 0 0 48%; font-size: 13pt; font-weight: bold; }
    .grand span { float: right; }

    table.next { width: 100%; border-collapse: separate; border-spacing: 2mm 0; margin: 0 -2mm; }
    table.next td { background: #f6f7fa; border-radius: 3mm; padding: 3.5mm; vertical-align: top; width: 33%; }
    table.next .no { color: #c80c92; font-weight: bold; font-size: 14pt; }
    .contact { margin-top: 6mm; border-top: 2px solid #c80c92; padding-top: 3mm; }
    .terms { margin-top: 5mm; font-size: 7pt; color: #777; }
    .terms li { margin-bottom: .8mm; }
</style>
</head>
<body>
<table class="top"><tr>
    <td>@if ($logo)<img src="{{ $logo }}" alt="{{ $company['company_name'] }}">@else<span class="brand">{{ $company['company_name'] }}</span>@endif</td>
    <td class="right"><strong>{{ $quote->number }}</strong>{{ $quote->created_at->format('d/m/Y') }}</td>
</tr></table>
<footer>
    <strong style="color:#c80c92">{{ $company['company_name'] }}</strong> · {{ $quote->number }}@if($company['vat']) · ΑΦΜ {{ $company['vat'] }}@endif
    <span class="r">{{ collect([$company['phone'], $company['email']])->filter()->implode(' · ') }}</span>
</footer>

<div class="hero">
    <div class="eyebrow">ΠΡΟΤΑΣΗ ΠΡΟΒΟΛΗΣ</div>
    <h1>{{ $customer ? 'για '.$customer : $quote->title }}</h1>
    @if ($customer && $quote->title)<div style="color:#c9cfe0; margin-top:1mm">{{ $quote->title }}</div>@endif
    <table class="facts"><tr>
        @if ($quote->starts_on)<td><div class="k">Έναρξη</div><div class="v">{{ $quote->starts_on->format('d/m/Y') }}</div></td>@endif
        <td><div class="k">Διάρκεια</div><div class="v">{{ $quote->days }} {{ $quote->days === 1 ? 'ημέρα' : 'ημέρες' }}</div></td>
        <td><div class="k">Μέσα</div><div class="v">{{ count($strip) ?: '—' }}</div></td>
        <td><div class="k">Επένδυση</div><div class="v" style="color:#ffb627">{{ $quote->money('total') }}</div></td>
        <td><div class="k">Ισχύει έως</div><div class="v">{{ $quote->valid_until->format('d/m/Y') }}</div></td>
    </tr></table>
</div>

<div class="intro">{!! nl2br(e($intro)) !!}</div>

@if ($strip)
    <h2>Τα μέσα της πρότασής σας</h2>
    <table class="strip"><tr>
        @foreach (array_slice($strip, 0, 6) as $b)
            <td>
                @if ($b['logo'])<img src="{{ $b['logo'] }}" alt="">@else<span class="textlogo" @if($b['color']) style="background: {{ $b['color'] }}" @endif>{{ $b['title'] }}</span>@endif
                <div class="n">{{ $b['subtitle'] ?: $b['title'] }}</div>
            </td>
        @endforeach
        @for ($i = count(array_slice($strip, 0, 6)); $i < 4; $i++)<td style="border:none"></td>@endfor
    </tr></table>
@endif

<h2>Αναλυτικά</h2>
@foreach ($media as $b)
    <div class="block" style="border-left-color: {{ $b['color'] ?: '#c80c92' }}">
        <table class="head"><tr>
            <td class="logo">@if ($b['logo'])<img src="{{ $b['logo'] }}" alt="">@else<div style="width:11mm; height:11mm; line-height:11mm; text-align:center; border-radius:5.5mm; color:#fff; font-weight:bold; font-size:9pt; background: {{ $b['color'] ?: '#c80c92' }}">{{ mb_strtoupper(mb_substr($b['title'], 0, 1)) }}</div>@endif</td>
            <td>
                <span class="t">{{ $b['title'] }}</span>@if ($b['subtitle'])<span class="s"> · {{ $b['subtitle'] }}</span>@endif
                @if ($b['text'])<div class="slogan">{{ $b['text'] }}</div>@endif
                @if ($b['chips'])<div>@foreach ($b['chips'] as $c)<span class="chip">{{ $c }}</span>@endforeach</div>@endif
            </td>
            <td class="sum">{{ $m($b['total']) }}</td>
        </tr></table>
        <table class="lines">
            @foreach ($b['lines'] as $line)
                <tr>
                    <td>{{ $line['label'] }}@if ($line['detail'])<div class="detail">{{ $line['detail'] }}</div>@endif</td>
                    <td class="num detail">{{ $line['quantity'] }} × {{ $m($line['unit_price']) }}</td>
                    <td class="num">{{ $m($line['amount']) }}</td>
                </tr>
            @endforeach
        </table>
    </div>
@endforeach

@if ($extras)
    <div class="block" style="border-left-color:#c80c92">
        <table class="lines" style="margin-top:0">
            @foreach ($extras as $b)
                @foreach ($b['lines'] as $line)
                    <tr>
                        <td><strong>{{ $line['label'] }}</strong>@if ($line['detail'])<div class="detail">{{ $line['detail'] }}</div>@endif
                            @foreach ($line['includes'] ?? [] as $inc)<div class="detail">· {{ $inc }}</div>@endforeach</td>
                        <td class="num">{{ $m($line['amount']) }}</td>
                    </tr>
                @endforeach
            @endforeach
        </table>
    </div>
@endif

<div style="page-break-inside: avoid">
<table class="totals">
    @if ($discounts)
        <tr><td>Σύνολο</td><td class="v">{{ $m((float) $quote->subtotal - array_sum(array_column($discounts, 'amount'))) }}</td></tr>
        @foreach ($discounts as $d)
            <tr><td style="color:#c80c92; font-weight:bold">{{ $d['label'] }}</td><td class="v" style="color:#c80c92; font-weight:bold">{{ $m($d['amount']) }}</td></tr>
        @endforeach
    @endif
    <tr><td>Σύνολο χωρίς ΦΠΑ</td><td class="v">{{ $quote->money('subtotal') }}</td></tr>
    <tr><td>ΦΠΑ {{ $vatLabel }}%</td><td class="v">{{ $quote->money('vat') }}</td></tr>
</table>
<div class="grand">Τελικό ποσό <span>{{ $quote->money('total') }}</span></div>
</div>

<div style="page-break-inside: avoid">
    <h2>Πώς προχωράμε</h2>
    <table class="next"><tr>
        <td><span class="no">1</span> <strong>Επιβεβαίωση</strong><br>Πατάτε «Προχώρηση» στο Studio σας ή μας απαντάτε σε αυτή την πρόταση.</td>
        <td><span class="no">2</span> <strong>Υλικό & πρόγραμμα</strong><br>Μας στέλνετε το υλικό σας ή το φτιάχνουμε εμείς, και ορίζουμε τις ώρες.</td>
        <td><span class="no">3</span> <strong>Στον αέρα</strong><br>Με την εξόφληση η καμπάνια ξεκινά την ημερομηνία που συμφωνήσαμε.</td>
    </tr></table>

    <div class="contact">
        @if ($by)
            <strong>{{ $by->name }}</strong> · {{ $company['company_name'] }}<br>
            {{ collect([$by->email, $company['phone']])->filter()->implode(' · ') }}
        @else
            <strong>{{ $company['company_name'] }}</strong><br>
            {{ collect([$company['phone'], $company['email']])->filter()->implode(' · ') }}
        @endif
    </div>

    <ul class="terms">
        <li>Οι τιμές ισχύουν έως {{ $quote->valid_until->format('d/m/Y') }}. Μετά, η πρόταση ανανεώνεται με τις τρέχουσες τιμές.</li>
        <li>Οι ακριβείς ώρες μετάδοσης ορίζονται από το πρόγραμμα του σταθμού μέσα στις ζώνες που συμφωνούνται, εκτός αν έχουν επιλεγεί συγκεκριμένες ώρες.</li>
        <li>Η καμπάνια ξεκινά μετά την επιβεβαίωση του προγράμματος, την έγκριση του υλικού και την εξόφληση. Το υλικό συμμορφώνεται με τη νομοθεσία για τη διαφήμιση.</li>
    </ul>
</div>
</body>
</html>
