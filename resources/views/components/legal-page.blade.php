@props(['title', 'updated'])
@php($s = \App\Models\Setting::allValues())
<x-layouts.site :title="$title.' — On Air Media Group'">
    <article class="mx-auto max-w-3xl px-4 sm:px-6 py-12 sm:py-16">
        <h1 class="text-3xl sm:text-4xl font-extrabold tracking-tight">{{ $title }}</h1>
        <p class="mt-2 text-slate">Τελευταία ενημέρωση: {{ $updated }}</p>
        <p class="mt-6 rounded-xl border border-amber-500/50 bg-amber-500/10 px-4 py-3 text-[15px]">
            Προσχέδιο υπό νομικό έλεγχο. Το τελικό κείμενο θα ανακοινωθεί πριν την επίσημη λειτουργία.
        </p>
        <div class="mt-8 text-[17px] leading-relaxed [&_h2]:mt-10 [&_h2]:text-xl [&_h2]:font-bold [&_p]:mt-4 [&_ul]:mt-4 [&_ul]:list-disc [&_ul]:pl-6 [&_li]:mt-1 [&_a]:text-onair [&_a]:underline">
            {{ $slot }}
            <h2>Στοιχεία επικοινωνίας</h2>
            <p>
                {{ $s['company_name'] }}@if($s['address'])<br>{{ $s['address'] }}@endif
                @if($s['vat'])<br>ΑΦΜ {{ $s['vat'] }}@endif @if($s['gemi']) · ΓΕΜΗ {{ $s['gemi'] }}@endif
                @if($s['email'])<br><a href="mailto:{{ $s['email'] }}">{{ $s['email'] }}</a>@endif
                @if($s['phone'])<br>{{ $s['phone'] }}@endif
            </p>
        </div>
    </article>
</x-layouts.site>
