@php
    $s = \App\Models\Setting::allValues();
    $map = $section->setting('map_url');
    $cards = array_values(array_filter([
        $s['phone'] ? ['icon' => 'phone', 'label' => 'Τηλέφωνο', 'value' => $s['phone'], 'href' => 'tel:'.preg_replace('/[^0-9+]/', '', $s['phone'])] : null,
        $s['email'] ? ['icon' => 'mail', 'label' => 'Email', 'value' => $s['email'], 'href' => 'mailto:'.$s['email']] : null,
        $s['address'] ? ['icon' => 'pin', 'label' => 'Διεύθυνση', 'value' => $s['address'], 'href' => is_string($map) && str_starts_with($map, 'https://') ? $map : null] : null,
        $section->setting('hours') ? ['icon' => 'clock', 'label' => 'Ωράριο', 'value' => $section->setting('hours'), 'href' => null] : null,
    ]));
@endphp
<section class="mx-auto max-w-7xl px-4 sm:px-6 py-16" aria-labelledby="ct-{{ $section->id }}">
    <div class="grid gap-10 lg:grid-cols-[1fr_1.4fr] lg:items-center">
        <div>
            <h2 id="ct-{{ $section->id }}" class="text-3xl sm:text-4xl font-extrabold tracking-tight">{{ $section->title ?: 'Μιλήστε μαζί μας' }}</h2>
            <p class="mt-3 text-lg text-slate">{{ $section->subtitle ?: 'Θα χαρούμε να σχεδιάσουμε μαζί την καμπάνια σας.' }}</p>
            <a href="{{ route('quote.builder') }}" class="mt-6 inline-flex items-center gap-2 rounded-full bg-onair px-6 py-3.5 font-semibold text-white hover:bg-onair-dark">Βγάλτε προσφορά <x-ui-icon name="arrow" class="h-4 w-4" /></a>
        </div>
        <ul class="grid gap-4 sm:grid-cols-2">
            @foreach ($cards as $c)
                <li class="relative rounded-3xl border border-line bg-surface p-6">
                    <span class="grid h-11 w-11 place-items-center rounded-2xl bg-onair/10 text-onair"><x-ui-icon :name="$c['icon']" class="h-5 w-5" /></span>
                    <p class="mt-4 text-sm text-slate">{{ $c['label'] }}</p>
                    @if ($c['href'])
                        <a href="{{ $c['href'] }}" class="mt-1 block whitespace-pre-line break-words text-lg font-semibold after:absolute after:inset-0 hover:text-onair" @if(str_starts_with($c['href'], 'https://')) target="_blank" rel="noopener" @endif>{{ $c['value'] }}</a>
                    @else
                        <p class="mt-1 whitespace-pre-line text-lg font-semibold">{{ $c['value'] }}</p>
                    @endif
                </li>
            @endforeach
        </ul>
    </div>
</section>
