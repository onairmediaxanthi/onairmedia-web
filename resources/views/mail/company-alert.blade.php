<x-mail::message>
# {{ $label }}

{{ $summary }}

@if ($business)
**{{ $business->legal_name }}**@if($business->trade_name) ({{ $business->trade_name }})@endif<br>
ΑΦΜ {{ $business->vat }} · {{ $business->typeLabel() }}<br>
{{ $business->contact_person }} · {{ $business->mobile }} · {{ $customer->email }}
@elseif ($customer)
{{ $customer->name }} · {{ $customer->email }}
@endif

@if ($text)
<x-mail::panel>
{!! nl2br(e($text)) !!}
</x-mail::panel>

Απαντήστε από τη διαχείριση με «Μήνυμα / σημείωση»: ο πελάτης το λαμβάνει με email και το βλέπει στην παραγγελία του.
@endif

@if ($order && $type !== 'quote_submitted')
**Παραγγελία {{ $order->number }}** · {{ $order->stageLabel() }} · {{ $order->money() }}
@elseif ($quote)
<x-mail::table>
| | |
|:--|--:|
@foreach ($quote->items as $line)
| {{ $line['label'] }}@if($line['detail'])<br><small>{{ $line['detail'] }}</small>@endif | {{ \App\Services\Quotes\QuoteCalculator::money($line['amount']) }} |
@endforeach
| **Σύνολο χωρίς ΦΠΑ** | **{{ $quote->money('subtotal') }}** |
| ΦΠΑ {{ rtrim(rtrim((string) $quote->vat_rate, '0'), '.') }}% | {{ $quote->money('vat') }} |
| **Τελικό ποσό** | **{{ $quote->money('total') }}** |
</x-mail::table>

@if ($quote->starts_on)Έναρξη: {{ $quote->starts_on->format('d/m/Y') }} · @endif Διάρκεια: {{ $quote->days }} ημέρες · Ισχύει έως {{ $quote->valid_until->format('d/m/Y') }}

@if ($quote->customer_notes)
**Σημειώσεις πελάτη:** {{ $quote->customer_notes }}
@endif
@endif

<x-mail::button :url="$adminUrl">Άνοιγμα στη διαχείριση</x-mail::button>
</x-mail::message>
