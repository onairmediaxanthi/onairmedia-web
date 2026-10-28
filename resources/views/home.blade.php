<x-layouts.site>
    @forelse ($sections as $section)
        <div @if($section->anchor) id="{{ $section->anchor }}" @endif class="scroll-mt-16 {{ $section->visibilityClasses() }}" data-section="{{ $section->type }}">
            @include('home.sections.'.$section->type, ['section' => $section])
        </div>
    @empty
        <section class="mx-auto max-w-3xl px-4 py-24 text-center">
            <h1 class="text-3xl font-extrabold">Η αρχική δεν έχει ακόμα ενότητες</h1>
            <p class="mt-3 text-slate">Προσθέστε ενότητες από τη διαχείριση: Αρχική σελίδα → Ενότητες αρχικής.</p>
        </section>
    @endforelse
</x-layouts.site>
