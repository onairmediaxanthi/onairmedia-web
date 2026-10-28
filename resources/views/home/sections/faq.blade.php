@php($items = array_values(array_filter((array) $section->setting('items', []), fn ($i) => ! empty($i['q']) && ! empty($i['a']))))
@if ($items)
<section class="mx-auto max-w-4xl px-4 sm:px-6 py-16" aria-labelledby="fq-{{ $section->id }}">
    <h2 id="fq-{{ $section->id }}" class="text-3xl sm:text-4xl font-extrabold tracking-tight">{{ $section->title ?: 'Συχνές ερωτήσεις' }}</h2>
    @if ($section->subtitle)<p class="mt-3 text-lg text-slate">{{ $section->subtitle }}</p>@endif
    <div class="mt-8 divide-y divide-line overflow-hidden rounded-3xl border border-line bg-surface">
        @foreach ($items as $it)
            <details class="group px-6 py-5 [&_summary::-webkit-details-marker]:hidden">
                <summary class="flex cursor-pointer list-none items-center justify-between gap-4 text-left text-lg font-semibold">
                    {{ $it['q'] }}
                    <span class="grid h-8 w-8 shrink-0 place-items-center rounded-full border border-line transition-transform group-open:rotate-45" aria-hidden="true">+</span>
                </summary>
                <p class="mt-3 whitespace-pre-line text-slate">{{ $it['a'] }}</p>
            </details>
        @endforeach
    </div>
    @if ($section->setting('schema', true))
        @php($ld = ['@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => array_map(fn ($i) => ['@type' => 'Question', 'name' => $i['q'], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $i['a']]], $items)])
        <script type="application/ld+json">{!! json_encode($ld, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}</script>
    @endif
</section>
@endif
