@php
    $items = $section->setting('items') ?: [
        ['title' => 'Ραδιόφωνο', 'text' => 'Σποτ σε 12 σταθμούς, από τα λαϊκά ως το rock. Τυχαίες ώρες της ζώνης ή ακριβώς τις ώρες που θέλετε.', 'url' => null],
        ['title' => 'thrakitoday.gr', 'text' => 'Banner δίπλα στις ειδήσεις και χορηγούμενα άρθρα για την επιχείρησή σας.', 'url' => null],
        ['title' => 'Οθόνη Πόλης', 'text' => 'Η ψηφιακή πινακίδα LED στην πόλη, με την προβολή σας όλη μέρα.', 'url' => null],
    ];
    $safe = fn ($u) => is_string($u) && preg_match('#^(/(?!/)|https://)#', $u) ? $u : null;
@endphp
<section class="mx-auto max-w-7xl px-4 sm:px-6 pt-20" aria-labelledby="media-title-{{ $section->id }}">
    <div class="max-w-2xl">
        <h2 id="media-title-{{ $section->id }}" class="text-3xl sm:text-4xl font-extrabold tracking-tight">{{ $section->title ?: 'Ακούγεσαι. Φαίνεσαι. Μετράς.' }}</h2>
        <p class="mt-3 text-lg text-slate">{{ $section->subtitle ?: 'Τρία μέσα, ένα κοινό: όλη η Ξάνθη. Συνδυάστε τα όπως ταιριάζει στην επιχείρησή σας.' }}</p>
    </div>
    <div class="mt-10 grid gap-px overflow-hidden rounded-2xl border border-line bg-line sm:grid-cols-2 {{ count($items) >= 3 ? 'lg:grid-cols-3' : '' }}">
        @foreach ($items as $item)
            <div class="relative bg-surface p-7">
                <h3 class="text-xl font-bold">
                    @if ($u = $safe($item['url'] ?? null))
                        <a href="{{ $u }}" class="after:absolute after:inset-0 hover:text-onair">{{ $item['title'] }}</a>
                    @else
                        {{ $item['title'] }}
                    @endif
                </h3>
                <p class="mt-2 text-slate">{{ $item['text'] }}</p>
            </div>
        @endforeach
    </div>
    @if ($note = $section->setting('note', 'Σύντομα θα στήνετε και θα πληρώνετε την καμπάνια σας online, εδώ.'))
        <p class="mt-6 text-slate">{{ $note }}</p>
    @endif
</section>
