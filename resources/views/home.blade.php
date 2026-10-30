<x-layouts.site>
    @forelse ($sections as $section)
        @php($w = $section->wrapperAttributes())
        <div @if($section->anchor) id="{{ $section->anchor }}" @endif class="scroll-mt-16 {{ $section->visibilityClasses() }} {{ $w['class'] }}"
             @if($w['style']) style="{{ $w['style'] }}" @endif data-section="{{ $section->type }}"
             data-pad="{{ $w['data-pad'] }}" data-width="{{ $w['data-width'] }}" data-align="{{ $w['data-align'] }}" data-title="{{ $w['data-title'] }}"
             @if($w['animate'] && ! $loop->first) data-reveal @endif>
            @include('home.sections.'.$section->type, ['section' => $section])
        </div>
    @empty
        <section class="mx-auto max-w-3xl px-4 py-24 text-center">
            <h1 class="text-3xl font-extrabold">Η αρχική δεν έχει ακόμα ενότητες</h1>
            <p class="mt-3 text-slate">Προσθέστε ενότητες από τη διαχείριση: Αρχική σελίδα → Ενότητες αρχικής.</p>
        </section>
    @endforelse
</x-layouts.site>
