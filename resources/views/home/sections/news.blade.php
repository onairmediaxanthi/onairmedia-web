@php
    $items = array_slice($news, 0, (int) $section->setting('count', 7));
    $lead = $items[0] ?? null;
    $rest = array_slice($items, 1);
    $fmtDate = fn ($iso) => $iso ? \Illuminate\Support\Carbon::parse($iso)->locale('el')->isoFormat('D MMM, HH:mm') : null;
@endphp
@if ($lead)
    <section class="border-t border-line bg-surface" aria-labelledby="news-title-{{ $section->id }}">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 py-16">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <h2 id="news-title-{{ $section->id }}" class="text-3xl sm:text-4xl font-extrabold tracking-tight">{{ $section->title ?: 'Η Θράκη σήμερα' }}</h2>
                <a href="https://thrakitoday.gr" target="_blank" rel="noopener" class="font-semibold text-onair hover:underline underline-offset-4">Όλες οι ειδήσεις στο thrakitoday.gr</a>
            </div>

            <div class="mt-8 grid gap-8 lg:grid-cols-[1.25fr_1fr]">
                <article class="group relative">
                    @if ($lead['image'])
                        <img src="{{ $lead['image'] }}" alt="" loading="lazy" class="aspect-[16/10] w-full rounded-2xl object-cover bg-paper">
                    @endif
                    <p class="mt-4 text-sm text-slate">{{ $lead['category'] }}@if($lead['date']) · {{ $fmtDate($lead['date']) }}@endif</p>
                    <h3 class="mt-1 text-2xl sm:text-3xl font-bold leading-tight">
                        <a href="{{ $lead['url'] }}" target="_blank" rel="noopener" class="after:absolute after:inset-0 group-hover:underline underline-offset-4 decoration-2">{{ $lead['title'] }}</a>
                    </h3>
                    @if ($lead['excerpt'])<p class="mt-3 text-slate max-w-prose">{{ $lead['excerpt'] }}</p>@endif
                </article>

                <ul class="divide-y divide-line">
                    @foreach ($rest as $item)
                        <li class="group relative flex gap-4 py-4 first:pt-0">
                            @if ($item['image'])
                                <img src="{{ $item['image'] }}" alt="" loading="lazy" class="h-20 w-28 shrink-0 rounded-lg object-cover bg-paper">
                            @endif
                            <div class="min-w-0">
                                <p class="text-xs text-slate">{{ $item['category'] }}@if($item['date']) · {{ $fmtDate($item['date']) }}@endif</p>
                                <h3 class="mt-1 font-semibold leading-snug line-clamp-3">
                                    <a href="{{ $item['url'] }}" target="_blank" rel="noopener" class="after:absolute after:inset-0 group-hover:underline underline-offset-4">{{ $item['title'] }}</a>
                                </h3>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </section>
@endif
