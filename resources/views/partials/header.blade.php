@php($nav = [
    ['Σταθμοί', route('home').'#stathmoi'],
    ['Ειδήσεις', route('home').'#eidiseis'],
    ['Διαφήμιση', route('home').'#diafimisi'],
])
<header x-data="{ open: false }" class="sticky top-0 z-40 bg-surface/90 backdrop-blur border-b border-line">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 h-16 flex items-center gap-6">
        <a href="{{ route('home') }}" wire:navigate class="flex items-center gap-2 shrink-0" aria-label="On Air Media Group, αρχική">
            <span class="relative inline-flex h-3 w-3" aria-hidden="true">
                <span class="absolute inline-flex h-full w-full rounded-full bg-onair opacity-60 motion-safe:animate-ping"></span>
                <span class="relative inline-flex h-3 w-3 rounded-full bg-onair"></span>
            </span>
            <span class="font-extrabold tracking-tight text-lg leading-none">On Air <span class="font-medium">Media</span></span>
        </a>

        <nav class="hidden md:flex items-center gap-1 text-[15px] font-medium" aria-label="Κύριο μενού">
            @foreach ($nav as [$label, $href])
                <a href="{{ $href }}" class="px-3 py-2 rounded-md text-slate hover:text-ink hover:bg-paper">{{ $label }}</a>
            @endforeach
        </nav>

        <div class="ml-auto flex items-center gap-2">
            <button type="button" x-data @click="$store.theme.toggle()"
                    class="h-10 w-10 grid place-items-center rounded-full text-slate hover:text-ink hover:bg-paper"
                    :aria-label="$store.theme.dark ? 'Φωτεινή εμφάνιση' : 'Σκοτεινή εμφάνιση'"
                    :title="$store.theme.dark ? 'Φωτεινή εμφάνιση' : 'Σκοτεινή εμφάνιση'">
                <svg x-show="!$store.theme.dark" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M21 12.8A9 9 0 1111.2 3a7 7 0 009.8 9.8z"/></svg>
                <svg x-show="$store.theme.dark" x-cloak class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/></svg>
            </button>
            <a href="{{ route('home') }}#diafimisi" class="hidden sm:inline-flex items-center rounded-full bg-onair px-5 py-2.5 text-sm font-semibold text-white hover:bg-onair-dark">
                Διαφημιστείτε
            </a>
            <button type="button" class="md:hidden p-2 -mr-2 rounded-md" @click="open = !open" :aria-expanded="open" aria-controls="mobile-nav">
                <span class="sr-only">Μενού</span>
                <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path x-show="!open" d="M4 7h16M4 12h16M4 17h16"/><path x-show="open" x-cloak d="M6 6l12 12M18 6L6 18"/></svg>
            </button>
        </div>
    </div>

    <nav id="mobile-nav" x-show="open" x-cloak x-transition.opacity class="md:hidden border-t border-line bg-surface" aria-label="Μενού κινητού">
        <div class="px-4 py-3 flex flex-col">
            @foreach ($nav as [$label, $href])
                <a href="{{ $href }}" @click="open = false" class="py-3 text-lg font-medium border-b border-line last:border-0">{{ $label }}</a>
            @endforeach
            <a href="{{ route('home') }}#diafimisi" @click="open = false" class="mt-3 text-center rounded-full bg-onair px-5 py-3 font-semibold text-white">Διαφημιστείτε</a>
        </div>
    </nav>
</header>
