<x-mail::message>
# Καλώς ήρθατε, {{ $user->name }}

@if ($by){{ $by->name }} σας@else Σας@endif έδωσε πρόσβαση στη διαχείριση του {{ \App\Models\Setting::get('company_name') }}, με ρόλο **{{ $user->role->label() }}**.

1. Ορίστε τον κωδικό σας από το κουμπί.
2. Στην πρώτη σύνδεση θα ρυθμίσετε τον έλεγχο ταυτότητας δύο βημάτων (2FA) με μια εφαρμογή όπως το Google Authenticator ή το Microsoft Authenticator. Είναι υποχρεωτικός για όλους.

<x-mail::button :url="$url">Ορισμός κωδικού</x-mail::button>

Ο σύνδεσμος ισχύει 24 ώρες. Αν λήξει, ζητήστε νέα πρόσκληση.
</x-mail::message>
