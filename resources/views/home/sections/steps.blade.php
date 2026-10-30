@php
    $items = (array) $section->setting('items') ?: [
        ['title' => 'Δωρεάν εγγραφή', 'text' => 'Ανοίγετε λογαριασμό επιχείρησης σε ένα λεπτό.'],
        ['title' => 'Βγάζετε προσφορά', 'text' => 'Με τον οδηγό ή μόνοι σας, με τιμή μπροστά σας.'],
        ['title' => 'Προχωράτε', 'text' => 'Στέλνετε την παραγγελία και επιβεβαιώνουμε το πρόγραμμα.'],
        ['title' => 'Στον αέρα', 'text' => 'Η καμπάνια σας ακούγεται και φαίνεται σε όλη την πόλη.'],
    ];
@endphp
<section class="mx-auto max-w-7xl px-4 sm:px-6 py-16" aria-labelledby="sp-{{ $section->id }}">
    <div class="max-w-2xl">
        <h2 id="sp-{{ $section->id }}" class="text-3xl sm:text-4xl font-extrabold tracking-tight">{{ $section->title ?: 'Πώς λειτουργεί' }}</h2>
        @if ($section->subtitle)<p class="mt-3 text-lg text-slate">{{ $section->subtitle }}</p>@endif
    </div>
    <ol class="relative mt-10 grid gap-6 sm:grid-cols-2 {{ count($items) >= 4 ? 'lg:grid-cols-4' : 'lg:grid-cols-3' }}">
        @foreach ($items as $i => $it)
            <li class="relative rounded-3xl border border-line bg-surface p-6">
                <span class="grid h-12 w-12 place-items-center rounded-2xl bg-onair text-xl font-extrabold text-white freq">{{ $i + 1 }}</span>
                <h3 class="mt-4 text-xl font-bold">{{ $it['title'] ?? '' }}</h3>
                @if (! empty($it['text']))<p class="mt-2 text-slate">{{ $it['text'] }}</p>@endif
            </li>
        @endforeach
    </ol>
    <div class="mt-8">
        <a href="{{ route('quote.builder') }}" class="inline-flex items-center gap-2 rounded-full bg-onair px-6 py-3.5 font-semibold text-white hover:bg-onair-dark">{{ $section->setting('cta_label') ?: 'Ξεκινήστε τώρα' }} <x-ui-icon name="arrow" class="h-4 w-4" /></a>
    </div>
</section>
