<x-mail::message>
# {{ $event->title }}

Παραγγελία **{{ $order->number }}**@if($order->quote) · «{{ $order->quote->title }}»@endif

**Στάδιο:** {{ $order->stageLabel(true) }}

{{ $order->stageInfo()['text'] }}

@if ($event->note)
<x-mail::panel>
{!! nl2br(e($event->note)) !!}
</x-mail::panel>
@endif

@if (in_array($order->stage, ['scheduled', 'running'], true) && $order->starts_on && $order->ends_on)
**Διάρκεια καμπάνιας:** {{ $order->starts_on->format('d/m/Y') }} έως {{ $order->ends_on->format('d/m/Y') }}
@endif

@if ($showPayment)
@if ($order->payment_method === 'bank')
## Στοιχεία πληρωμής

Ποσό: **{{ $order->money('balance') }}** (με ΦΠΑ)@if($order->payment_due_on) · έως **{{ $order->payment_due_on->format('d/m/Y') }}**@endif

Αιτιολογία κατάθεσης: **{{ $order->number }}**

@forelse ($accounts as $a)
**{{ $a['bank'] }}**<br>
IBAN: {{ $a['iban'] }}<br>
Δικαιούχος: {{ $a['beneficiary'] }}

@empty
Θα σας στείλουμε τα στοιχεία του λογαριασμού μας τηλεφωνικά.
@endforelse

Μόλις κάνετε την κατάθεση, πατήστε «Έκανα την κατάθεση» στη σελίδα της παραγγελίας. Έτσι την εντοπίζουμε πιο γρήγορα.
@elseif ($order->payment_method === 'office')
Μπορείτε να εξοφλήσετε στο γραφείο μας με μετρητά ή κάρτα. Ποσό: **{{ $order->money('balance') }}** (με ΦΠΑ).
@endif
@endif

<x-mail::button :url="$url">Δείτε την παραγγελία</x-mail::button>

Με εκτίμηση,<br>
{{ \App\Models\Setting::get('company_name') }}
</x-mail::message>
