<x-mail::message>
# Η προσφορά σας σας περιμένει

Η προσφορά **{{ $quote->number }}** «{{ $quote->title }}» είναι αποθηκευμένη στο Studio σας, με τελικό ποσό **{{ $quote->money('total') }}** (με ΦΠΑ).

Οι τιμές της ισχύουν έως **{{ $quote->valid_until->format('d/m/Y') }}**. Μπορείτε να την προχωρήσετε, να την αλλάξετε ή να ζητήσετε μια καινούργια.

<x-mail::button :url="$url">Άνοιγμα προσφοράς</x-mail::button>

Έχετε απορίες; Απαντήστε σε αυτό το email ή καλέστε μας@if(\App\Models\Setting::get('phone')) στο {{ \App\Models\Setting::get('phone') }}@endif.

<small>Δεν θέλετε άλλες υπενθυμίσεις για αυτή την προσφορά; [Σταματήστε τις εδώ]({{ $stopUrl }}).</small>
</x-mail::message>
