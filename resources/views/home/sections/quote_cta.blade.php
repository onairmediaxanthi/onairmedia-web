@php
    $user = auth()->user();
    $secondaryUrl = $user ? route('account') : route('register');
    $secondary = $user ? 'Οι προσφορές μου' : ($section->setting('secondary_label') ?: 'Δωρεάν εγγραφή επιχείρησης');
@endphp
<section class="mx-auto max-w-7xl px-4 sm:px-6 py-10" aria-labelledby="qc-{{ $section->id }}">
    <div class="relative isolate overflow-hidden rounded-3xl bg-onair px-6 py-10 text-white sm:px-12 sm:py-14">
        <div class="absolute -right-24 -top-24 -z-10 h-80 w-80 rounded-full bg-white/10" aria-hidden="true"></div>
        <div class="absolute -bottom-32 right-40 -z-10 h-72 w-72 rounded-full bg-night/20" aria-hidden="true"></div>
        <div class="grid items-center gap-10 lg:grid-cols-[1.3fr_1fr]">
            <div>
                <h2 id="qc-{{ $section->id }}" class="text-3xl sm:text-4xl font-extrabold tracking-tight leading-tight">{{ $section->title ?: 'Η διαφήμισή σας, σε δύο λεπτά.' }}</h2>
                @if ($section->subtitle)<p class="mt-3 max-w-xl text-lg text-white/85">{{ $section->subtitle }}</p>@endif
                <div class="mt-7 flex flex-wrap gap-3">
                    <a href="{{ route('quote.builder') }}" class="inline-flex items-center gap-2 rounded-full bg-white px-6 py-3.5 font-bold text-onair hover:bg-white/90">
                        {{ $section->setting('primary_label') ?: 'Βγάλτε προσφορά σε 2 λεπτά' }}
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                    </a>
                    <a href="{{ $secondaryUrl }}" class="inline-flex rounded-full border-2 border-white/60 px-6 py-3 font-semibold hover:bg-white/10">{{ $secondary }}</a>
                    <button type="button" x-data @click="$dispatch('open-callback')" class="inline-flex items-center gap-2 rounded-full px-4 py-3 font-semibold text-white/90 underline-offset-4 hover:underline">
                        <x-ui-icon name="phone" class="h-4 w-4" /> ή να σας καλέσουμε
                    </button>
                </div>
            </div>
            @if ($section->setting('show_steps', true))
                <ol class="space-y-3">
                    @foreach (['Απαντάτε σε 4 ερωτήσεις', 'Βλέπετε έως 3 προτάσεις με τιμή', 'Την κρατάτε σε PDF ή την προχωράτε'] as $i => $text)
                        <li class="flex items-center gap-4 rounded-2xl bg-white/10 px-5 py-4 backdrop-blur">
                            <span class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-white font-extrabold text-onair freq">{{ $i + 1 }}</span>
                            <span class="font-semibold">{{ $text }}</span>
                        </li>
                    @endforeach
                </ol>
            @endif
        </div>
    </div>
</section>
