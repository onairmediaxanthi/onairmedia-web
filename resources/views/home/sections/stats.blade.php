@php($items = (array) $section->setting('items', []))
@if ($items)
<section class="mx-auto max-w-7xl px-4 sm:px-6 py-16" aria-labelledby="st-{{ $section->id }}">
    @if ($section->title || $section->subtitle)
        <div class="max-w-2xl">
            @if ($section->title)<h2 id="st-{{ $section->id }}" class="text-3xl sm:text-4xl font-extrabold tracking-tight">{{ $section->title }}</h2>@endif
            @if ($section->subtitle)<p class="mt-3 text-lg text-slate">{{ $section->subtitle }}</p>@endif
        </div>
    @endif
    <dl class="mt-10 grid gap-6 sm:grid-cols-2 {{ count($items) >= 4 ? 'lg:grid-cols-4' : 'lg:grid-cols-'.count($items) }}">
        @foreach ($items as $it)
            <div class="rounded-3xl border border-line bg-surface p-7">
                <dd class="text-5xl font-extrabold tracking-tight text-[var(--sec-accent,var(--color-onair))] freq" @if($section->setting('count_up', true)) data-count="{{ $it['value'] ?? '' }}" @endif>{{ $it['value'] ?? '' }}</dd>
                <dt class="mt-2 text-lg font-semibold">{{ $it['label'] ?? '' }}</dt>
                @if (! empty($it['note']))<p class="mt-1 text-sm text-slate">{{ $it['note'] }}</p>@endif
            </div>
        @endforeach
    </dl>
</section>
@endif
