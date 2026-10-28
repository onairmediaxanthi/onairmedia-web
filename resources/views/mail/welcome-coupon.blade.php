<x-mail::message>
# Καλώς ήρθατε στο On Air Media Group!

Σας ευχαριστούμε που εγγραφήκατε. Για την πρώτη σας καμπάνια σας χαρίζουμε **{{ $coupon->valueLabel() }} έκπτωση**.

<x-mail::panel>
Ο κωδικός σας: **{{ $coupon->code }}**
</x-mail::panel>

Γράψτε τον στο ταμείο, όταν προχωράτε μια προσφορά από το Studio σας. Ισχύει έως **{{ $coupon->ends_at->format('d/m/Y') }}**, μία φορά@if($coupon->min_subtotal), για καμπάνιες από {{ \App\Services\Quotes\QuoteCalculator::money($coupon->min_subtotal) }} χωρίς ΦΠΑ@endif.

<x-mail::button :url="$url">Φτιάξτε την καμπάνια σας</x-mail::button>

Με εκτίμηση,<br>
{{ \App\Models\Setting::get('company_name') }}
</x-mail::message>
