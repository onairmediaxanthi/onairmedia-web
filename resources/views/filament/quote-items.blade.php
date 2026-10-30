@php($record = $getRecord())
@php($m = fn ($v) => \App\Services\Quotes\QuoteCalculator::money($v))
<div style="overflow-x:auto">
<table style="width:100%; border-collapse:collapse; font-size:14px">
    <thead><tr style="text-align:left; border-bottom:1px solid rgba(127,127,127,.3)">
        <th style="padding:6px 4px">Περιγραφή</th><th style="padding:6px 4px; text-align:right">Μονάδα</th>
        <th style="padding:6px 4px; text-align:right">Ποσ.</th><th style="padding:6px 4px; text-align:right">Ποσό</th>
    </tr></thead>
    <tbody>
    @foreach ($record->groupedItems() as $group => $lines)
        <tr><td colspan="4" style="padding:10px 4px 4px; font-weight:700; color:#c80c92">{{ $group }}</td></tr>
        @foreach ($lines as $line)
            <tr style="border-bottom:1px solid rgba(127,127,127,.15)">
                <td style="padding:6px 4px"><strong>{{ $line['label'] }}</strong>@if($line['detail'])<br><span style="opacity:.7">{{ $line['detail'] }}</span>@endif</td>
                <td style="padding:6px 4px; text-align:right; white-space:nowrap">{{ $m($line['unit_price']) }}</td>
                <td style="padding:6px 4px; text-align:right">{{ $line['quantity'] }}</td>
                <td style="padding:6px 4px; text-align:right; white-space:nowrap">{{ $m($line['amount']) }}</td>
            </tr>
        @endforeach
    @endforeach
    </tbody>
    <tfoot>
        <tr><td colspan="3" style="padding:8px 4px 2px; text-align:right">Χωρίς ΦΠΑ</td><td style="padding:8px 4px 2px; text-align:right">{{ $record->money('subtotal') }}</td></tr>
        <tr><td colspan="3" style="padding:2px 4px; text-align:right">ΦΠΑ {{ rtrim(rtrim((string) $record->vat_rate, '0'), '.') }}%</td><td style="padding:2px 4px; text-align:right">{{ $record->money('vat') }}</td></tr>
        <tr><td colspan="3" style="padding:4px; text-align:right; font-weight:800; font-size:16px">Σύνολο</td><td style="padding:4px; text-align:right; font-weight:800; font-size:16px">{{ $record->money('total') }}</td></tr>
    </tfoot>
</table>
</div>
