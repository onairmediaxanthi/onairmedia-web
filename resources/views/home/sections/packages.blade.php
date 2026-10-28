@php
    $list = $packages[$section->id] ?? collect();
    $canSeePrices = $canSeePrices ?? false;
    // Συνδεδεμένος αλλά ανεπιβεβαίωτος → τον στέλνουμε να επιβεβαιώσει, όχι να ξαναγραφτεί
    $registerUrl = auth()->check() ? route('verification.notice') : route('register');
    $unlockLabel = auth()->check() ? 'με επιβεβαίωση του email σας' : 'με δωρεάν εγγραφή επαγγελματία';
@endphp
@if ($list->isNotEmpty())
<section class="mx-auto max-w-7xl px-4 sm:px-6 py-20" aria-labelledby="pk-title-{{ $section->id }}">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div class="max-w-2xl">
            <h2 id="pk-title-{{ $section->id }}" class="text-3xl sm:text-4xl font-extrabold tracking-tight">{{ $section->title ?: 'Έτοιμα πακέτα προβολής' }}</h2>
            <p class="mt-3 text-lg text-slate">{{ $section->subtitle ?: 'Διαλέξτε ένα έτοιμο πακέτο ή ξεκινήστε από αυτό και φτιάξτε το δικό σας.' }}</p>
        </div>
        @unless ($canSeePrices)
            <a href="{{ $registerUrl }}" class="font-semibold text-onair hover:underline underline-offset-4">{{ auth()->check() ? 'Επιβεβαιώστε το email σας για να δείτε τιμές' : 'Δωρεάν εγγραφή για να δείτε τιμές' }}</a>
        @endunless
    </div>

    <ul class="mt-10 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
        @foreach ($list as $p)
            <li class="relative flex flex-col rounded-2xl border bg-surface overflow-hidden {{ $p->is_featured ? 'border-2 shadow-lg' : 'border-line' }}"
                @if($p->is_featured) style="border-color: {{ $p->color }}" @endif>
                {{-- Η «συχνότητα» του πακέτου: χρωματιστή μπάρα με τα μέσα που περιλαμβάνει --}}
                <div class="px-6 pt-6 pb-5" style="background: linear-gradient(135deg, {{ $p->color }}1f, transparent 70%)">
                    <div class="flex items-start justify-between gap-3">
                        <h3 class="text-2xl font-extrabold leading-tight">{{ $p->name }}</h3>
                        @if ($p->badge)
                            <span class="shrink-0 rounded-full px-3 py-1 text-xs font-bold text-white" style="background: {{ $p->color }}">{{ $p->badge }}</span>
                        @endif
                    </div>
                    @if ($p->tagline)<p class="mt-2 text-slate">{{ $p->tagline }}</p>@endif
                    @if ($media = $p->mediaList())
                        <ul class="mt-4 flex flex-wrap gap-1.5" aria-label="Μέσα">
                            @foreach ($media as $m)
                                <li class="rounded-full border border-line bg-surface px-2.5 py-1 text-xs font-medium">{{ \App\Models\Package::MEDIA[$m] ?? $m }}</li>
                            @endforeach
                        </ul>
                    @endif
                </div>

                <div class="px-6 pb-6 flex-1 flex flex-col">
                    @if ($p->includes)
                        <ul class="mt-1 space-y-2 text-[15px]">
                            @foreach ($p->includes as $line)
                                <li class="flex gap-2.5">
                                    <svg class="mt-1 h-4 w-4 shrink-0" style="color: {{ $p->color }}" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M16.7 5.3a1 1 0 010 1.4l-8 8a1 1 0 01-1.4 0l-4-4a1 1 0 111.4-1.4L8 12.6l7.3-7.3a1 1 0 011.4 0z" clip-rule="evenodd"/></svg>
                                    <span>{{ is_array($line) ? ($line['line'] ?? '') : $line }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @endif

                    <div class="mt-auto pt-6">
                        @if ($p->available_until)
                            <p class="mb-3 text-sm font-medium" style="color: {{ $p->color }}">Ισχύει έως {{ $p->available_until->locale('el')->isoFormat('D MMMM') }}</p>
                        @endif
                        @if ($canSeePrices && $p->price !== null)
                            <p class="flex items-baseline gap-2">
                                <span class="text-3xl font-extrabold freq">{{ \App\Models\Package::formatPrice($p->price) }}</span>
                                @if ($p->discount_percent)
                                    <span class="text-slate line-through freq">{{ \App\Models\Package::formatPrice($p->compare_at_price) }}</span>
                                @endif
                                <span class="text-sm text-slate">+ ΦΠΑ</span>
                            </p>
                            @if ($p->duration_days)<p class="text-sm text-slate">για {{ $p->duration_days }} ημέρες</p>@endif
                        @else
                            <a href="{{ $registerUrl }}" class="flex items-center justify-between gap-3 rounded-xl border border-dashed border-line px-4 py-3 hover:border-onair">
                                <span>
                                    <span class="block font-semibold">Δείτε την τιμή</span>
                                    <span class="block text-sm text-slate">{{ $unlockLabel }}</span>
                                </span>
                                <svg class="h-5 w-5 text-slate" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V7a4 4 0 018 0v4"/></svg>
                            </a>
                        @endif
                    </div>
                </div>
            </li>
        @endforeach
    </ul>
</section>
@endif
