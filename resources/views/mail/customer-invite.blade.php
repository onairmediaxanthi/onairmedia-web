<x-mail::message>
# Καλώς ήρθατε, {{ $business->contact_person }}

Σας ανοίξαμε λογαριασμό για την επιχείρηση **{{ $business->displayName() }}** στο Studio της On Air Media Group.

Εκεί βλέπετε τις προσφορές που σας ετοιμάζουμε, τις κατεβάζετε σε PDF, φτιάχνετε δικές σας καμπάνιες για ραδιόφωνο, thrakitoday.gr και πινακίδα LED, και τις προχωράτε όποτε θέλετε.

Για να ξεκινήσετε, ορίστε τον κωδικό σας:

<x-mail::button :url="$url">Ενεργοποίηση λογαριασμού</x-mail::button>

Ο σύνδεσμος ισχύει για {{ \App\Services\CustomerInvites::VALID_DAYS }} ημέρες. Αν δεν περιμένατε αυτό το email, απλώς αγνοήστε το.

Με εκτίμηση,<br>
{{ \App\Models\Setting::get('company_name') }}
</x-mail::message>
