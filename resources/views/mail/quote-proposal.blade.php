@php($m = fn ($v) => \App\Services\Quotes\QuoteCalculator::money($v))
<!doctype html>
<html lang="el">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width"><title>{{ $subjectLine }}</title></head>
<body style="margin:0;padding:0;background:#f1f2f6;font-family:Arial,Helvetica,sans-serif;color:#121a33">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f1f2f6;padding:24px 12px">
<tr><td align="center">
<table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background:#ffffff;border-radius:16px;overflow:hidden">
    <tr><td style="padding:22px 28px;border-bottom:3px solid #c80c92">
        @if ($logo)<img src="{{ $logo }}" alt="{{ $company['company_name'] }}" height="44" style="height:44px;display:block">@else<strong style="font-size:20px;color:#c80c92">{{ $company['company_name'] }}</strong>@endif
    </td></tr>
    <tr><td style="background:#121a33;padding:28px;color:#ffffff">
        <div style="color:#ffb627;font-size:12px;font-weight:bold;letter-spacing:2px">ΠΡΟΤΑΣΗ ΠΡΟΒΟΛΗΣ · {{ $quote->number }}</div>
        <div style="font-size:26px;font-weight:bold;line-height:1.2;margin-top:6px">{{ $business ? 'για '.$business->displayName() : $quote->title }}</div>
        <div style="margin-top:14px;font-size:14px;color:#c9cfe0">
            @if ($quote->starts_on)Έναρξη {{ $quote->starts_on->format('d/m/Y') }} · @endif{{ $quote->days }} ημέρες · ισχύει έως {{ $quote->valid_until->format('d/m/Y') }}
        </div>
    </td></tr>
    <tr><td style="padding:26px 28px 6px;font-size:15px;line-height:1.6;color:#2a3048">{!! nl2br(e($body)) !!}</td></tr>

    @if ($strip)
    <tr><td style="padding:14px 28px 4px">
        <div style="font-size:13px;font-weight:bold;color:#c80c92;margin-bottom:10px">Τα μέσα της πρότασης</div>
        <table role="presentation" cellpadding="0" cellspacing="0"><tr>
            @foreach (array_slice($strip, 0, 5) as $b)
                <td align="center" style="padding:0 10px 0 0">
                    <div style="border:1px solid #e3e3ea;border-radius:12px;width:92px;height:64px;display:table-cell;vertical-align:middle;text-align:center">
                        @if ($b['logo'])<img src="{{ $b['logo'] }}" alt="{{ $b['title'] }}" style="max-width:76px;max-height:46px">@else<span style="font-weight:bold;font-size:12px">{{ $b['title'] }}</span>@endif
                    </div>
                </td>
            @endforeach
        </tr></table>
    </td></tr>
    @endif

    <tr><td style="padding:16px 28px">
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:14px">
            @foreach ($blocks as $b)
                <tr><td style="padding:10px 0;border-bottom:1px solid #efeff3">
                    <strong>{{ $b['title'] }}</strong>@if($b['subtitle'])<span style="color:#777"> · {{ $b['subtitle'] }}</span>@endif
                    @foreach ($b['lines'] as $l)<div style="font-size:12px;color:#666;margin-top:2px">{{ $l['detail'] ?: $l['label'] }}</div>@endforeach
                </td><td align="right" style="padding:10px 0;border-bottom:1px solid #efeff3;white-space:nowrap;vertical-align:top"><strong>{{ $m($b['total']) }}</strong></td></tr>
            @endforeach
            @if ($d = \App\Services\Quotes\QuotePresenter::discount($quote))
                <tr><td style="padding:12px 0 2px;color:#c80c92;font-weight:bold">{{ $d['label'] }}</td><td align="right" style="padding:12px 0 2px;color:#c80c92;font-weight:bold">{{ $m($d['amount']) }}</td></tr>
            @endif
            <tr><td style="padding:12px 0 2px;color:#666">Χωρίς ΦΠΑ</td><td align="right" style="padding:12px 0 2px;color:#666">{{ $quote->money('subtotal') }}</td></tr>
            <tr><td style="padding:2px 0;color:#666">ΦΠΑ {{ rtrim(rtrim((string) $quote->vat_rate, '0'), '.') }}%</td><td align="right" style="padding:2px 0;color:#666">{{ $quote->money('vat') }}</td></tr>
            <tr><td style="padding:8px 0;font-size:18px;font-weight:bold">Τελικό ποσό</td><td align="right" style="padding:8px 0;font-size:20px;font-weight:bold;color:#c80c92">{{ $quote->money('total') }}</td></tr>
        </table>
    </td></tr>

    <tr><td align="center" style="padding:10px 28px 6px">
        <a href="{{ $url }}" style="display:inline-block;background:#c80c92;color:#ffffff;text-decoration:none;font-weight:bold;font-size:16px;padding:14px 30px;border-radius:999px">
            {{ $needsActivation ? 'Δείτε την πρόταση στο Studio σας' : 'Άνοιγμα πρότασης' }}
        </a>
        @if ($needsActivation)
            <div style="font-size:12px;color:#777;margin-top:10px">Με το κουμπί ενεργοποιείτε τον λογαριασμό που σας ανοίξαμε, ορίζοντας έναν κωδικό. Ο σύνδεσμος ισχύει {{ \App\Services\CustomerInvites::VALID_DAYS }} ημέρες.</div>
        @endif
        @if ($attachPdf)<div style="font-size:12px;color:#777;margin-top:8px">Θα βρείτε την πρόταση αναλυτικά και στο συνημμένο PDF.</div>@endif
    </td></tr>

    <tr><td style="padding:22px 28px;font-size:13px;color:#555;border-top:1px solid #efeff3">
        @if ($sender)<strong style="color:#121a33">{{ $sender->name }}</strong><br>@endif
        {{ $company['company_name'] }}@if($company['phone']) · {{ $company['phone'] }}@endif<br>
        @if ($sender?->email){{ $sender->email }}@elseif($company['email']){{ $company['email'] }}@endif
    </td></tr>
</table>
<div style="font-size:11px;color:#999;margin-top:12px">{{ $company['company_name'] }}@if($company['address']) · {{ $company['address'] }}@endif</div>
</td></tr>
</table>
</body>
</html>
