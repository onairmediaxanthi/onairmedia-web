<!doctype html>
<html lang="el">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width"><title>{{ $campaign->subject }}</title></head>
<body style="margin:0;padding:0;background:#f1f2f6;font-family:Arial,Helvetica,sans-serif;color:#121a33">
@if ($campaign->preheader)<div style="display:none;max-height:0;overflow:hidden;opacity:0">{{ $campaign->preheader }}</div>@endif
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f1f2f6;padding:24px 12px">
<tr><td align="center">
<table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background:#ffffff;border-radius:16px;overflow:hidden">
    <tr><td style="padding:22px 28px;border-bottom:3px solid #c80c92">
        @if ($logo)<img src="{{ $logo }}" alt="{{ $company['company_name'] }}" height="48" style="height:48px;display:block">@else<strong style="font-size:20px;color:#c80c92">{{ $company['company_name'] }}</strong>@endif
    </td></tr>
    <tr><td style="padding:28px 28px 8px;font-size:15px;line-height:1.6;color:#2a3048">
        @if ($greetingName)<p style="margin:0 0 14px">Καλησπέρα {{ $greetingName }},</p>@endif
        @if ($html)<div>{!! $html !!}</div>@endif
    </td></tr>

    @foreach ($packages as $p)
        <tr><td style="padding:10px 28px">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #e3e3ea;border-radius:14px;overflow:hidden">
                <tr><td style="background:{{ $p->color ?: '#c80c92' }};padding:16px 20px;color:#fff">
                    @if ($p->badge)<span style="display:inline-block;background:#fff;color:{{ $p->color ?: '#c80c92' }};font-size:12px;font-weight:bold;border-radius:99px;padding:3px 10px">{{ $p->badge }}</span>@endif
                    <div style="font-size:20px;font-weight:bold;margin-top:6px">{{ $p->name }}</div>
                    @if ($p->tagline)<div style="font-size:14px;opacity:.9">{{ $p->tagline }}</div>@endif
                </td></tr>
                <tr><td style="padding:14px 20px;font-size:14px;color:#2a3048">
                    @foreach ((array) $p->includes as $line)<div style="padding:3px 0">✓ {{ is_array($line) ? ($line['line'] ?? '') : $line }}</div>@endforeach
                    @if ($p->price !== null)
                        <div style="margin-top:10px;font-size:20px;font-weight:bold">{{ \App\Models\Package::formatPrice($p->price) }} <span style="font-size:13px;font-weight:normal;color:#777">+ ΦΠΑ</span>
                            @if ($p->discount_percent)<span style="font-size:14px;color:#999;text-decoration:line-through;margin-left:6px">{{ \App\Models\Package::formatPrice($p->compare_at_price) }}</span>@endif
                        </div>
                    @endif
                    @if ($p->available_until)<div style="font-size:12px;color:#c80c92;margin-top:4px">Ισχύει έως {{ $p->available_until->format('d/m/Y') }}</div>@endif
                    <div style="margin-top:12px"><a href="{{ route('quote.builder', ['tropos' => 'packages', 'paketo' => $p->id]) }}" style="color:#c80c92;font-weight:bold;text-decoration:none">Το θέλω →</a></div>
                </td></tr>
            </table>
        </td></tr>
    @endforeach

    @if ($coupon)
        <tr><td style="padding:14px 28px">
            <div style="border:2px dashed #c80c92;border-radius:14px;padding:16px;text-align:center;background:#fdf2f9">
                <div style="font-size:14px;color:#8a0b66">{{ $coupon->name }}: <strong>{{ $coupon->valueLabel() }} έκπτωση</strong></div>
                <div style="font-size:24px;font-weight:bold;letter-spacing:3px;margin-top:6px">{{ $coupon->code }}</div>
                <div style="font-size:12px;color:#777;margin-top:4px">Γράψτε τον στο ταμείο@if($coupon->ends_at) · έως {{ $coupon->ends_at->format('d/m/Y') }}@endif</div>
            </div>
        </td></tr>
    @endif

    <tr><td align="center" style="padding:18px 28px 26px">
        <a href="{{ $cta['url'] }}" style="display:inline-block;background:#c80c92;color:#ffffff;text-decoration:none;font-weight:bold;font-size:16px;padding:14px 30px;border-radius:999px">{{ $cta['label'] }}</a>
    </td></tr>

    <tr><td style="padding:18px 28px;font-size:12px;line-height:1.6;color:#777;border-top:1px solid #efeff3">
        Λαμβάνετε αυτό το email επειδή δηλώσατε στον λογαριασμό σας στο {{ preg_replace('#^https?://#', '', config('app.url')) }} ότι θέλετε να ενημερώνεστε για προσφορές.<br>
        <a href="{{ $unsubscribeUrl }}" style="color:#c80c92">Δεν θέλω άλλα τέτοια email</a> · διαγραφή με ένα κλικ, χωρίς σύνδεση.<br><br>
        {{ $company['company_name'] }}@if($company['vat']) · ΑΦΜ {{ $company['vat'] }}@endif @if($company['address'])<br>{{ $company['address'] }}@endif @if($company['phone']) · {{ $company['phone'] }}@endif @if($company['email']) · {{ $company['email'] }}@endif
    </td></tr>
</table>
</td></tr>
</table>
</body>
</html>
