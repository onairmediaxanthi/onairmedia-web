<x-mail::message>
# Αίτημα επικοινωνίας

**{{ $cb->fullName() }}** ζητά να τον καλέσουμε.

<x-mail::panel>
Κινητό: **[{{ $cb->mobile }}](tel:+30{{ $cb->mobile }})**<br>
ΑΦΜ: **{{ $cb->vat }}** (σωστός αριθμός ελέγχου)
@if ($business)
<br>Υπάρχει ήδη πελάτης: **{{ $business->legal_name }}**@if($business->city) · {{ $business->city }}@endif
@else
<br>Δεν υπάρχει ακόμα πελάτης με αυτό το ΑΦΜ.
@endif
</x-mail::panel>

@if ($cb->page)Το ζήτησε από τη σελίδα: {{ $cb->page }}@endif

<x-mail::button :url="url('/admin/aitimata/'.$cb->id.'/edit')">Άνοιγμα στη διαχείριση</x-mail::button>
</x-mail::message>
