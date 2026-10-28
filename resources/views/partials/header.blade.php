@php($nav = $menu ?? [])
<header x-data="{ open: false }" class="sticky top-0 z-40 bg-surface/90 backdrop-blur border-b border-line">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 h-16 flex items-center gap-3 sm:gap-6">
        <a href="{{ route('home') }}" wire:navigate class="flex shrink-0 items-center" aria-label="Αρχική σελίδα">
            <x-site-logo class="h-10 sm:h-12 w-auto max-w-none" />
        </a>

        <nav class="hidden lg:flex items-center gap-1 text-[15px] font-medium" aria-label="Κύριο μενού">
            @foreach ($nav as [$label, $href])
                <a href="{{ $href }}" class="px-3 py-2 rounded-md text-slate hover:text-ink hover:bg-paper">{{ $label }}</a>
            @endforeach
        </nav>

        <div class="ml-auto flex min-w-0 items-center gap-1.5 sm:gap-2">
            <button type="button" x-data @click="$store.theme.toggle()"
                    class="h-10 w-10 shrink-0 grid place-items-center rounded-full text-slate hover:text-ink hover:bg-paper"
                    :aria-label="$store.theme.dark ? 'Φωτεινή εμφάνιση' : 'Σκοτεινή εμφάνιση'"
                    :title="$store.theme.dark ? 'Φωτεινή εμφάνιση' : 'Σκοτεινή εμφάνιση'">
                <svg x-show="!$store.theme.dark" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M21 12.8A9 9 0 1111.2 3a7 7 0 009.8 9.8z"/></svg>
                <svg x-show="$store.theme.dark" x-cloak class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/></svg>
            </button>
            @auth
                @php($me = auth()->user())
                @php($who = $me->business?->displayName() ?? $me->name)
                <div class="relative hidden sm:block" x-data="{ menu: false }" @keydown.escape.window="menu = false" @click.outside="menu = false">
                    <button type="button" @click="menu = ! menu" :aria-expanded="menu" aria-haspopup="true"
                            class="flex items-center gap-2 rounded-full border border-line py-1.5 pl-1.5 pr-3 text-sm font-semibold hover:border-onair">
                        <span class="grid h-8 w-8 place-items-center rounded-full bg-onair text-xs font-extrabold text-white" aria-hidden="true">{{ mb_strtoupper(mb_substr($who, 0, 1)) }}</span>
                        <span class="max-w-32 lg:max-w-44 truncate">{{ $who }}</span>
                        <svg class="h-4 w-4 text-slate" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M6 9l6 6 6-6"/></svg>
                    </button>
                    <div x-show="menu" x-cloak x-transition.opacity.duration.150ms
                         class="absolute right-0 mt-2 w-64 overflow-hidden rounded-2xl border border-line bg-surface shadow-xl" role="menu">
                        <div class="border-b border-line px-4 py-3">
                            <p class="text-xs text-slate">Συνδεδεμένος ως</p>
                            <p class="truncate font-semibold">{{ $who }}</p>
                            <p class="truncate text-sm text-slate">{{ $me->email }}</p>
                        </div>
                        <a href="{{ route('account') }}" class="block px-4 py-2.5 text-sm hover:bg-paper" role="menuitem">Το Studio μου</a>
                        <a href="{{ route('quotes.index') }}" class="block px-4 py-2.5 text-sm hover:bg-paper" role="menuitem">Οι προσφορές μου</a>
                        <a href="{{ route('account.edit') }}" class="block px-4 py-2.5 text-sm hover:bg-paper" role="menuitem">Στοιχεία επιχείρησης</a>
                        <form method="POST" action="{{ route('logout') }}" class="border-t border-line">
                            @csrf
                            <button class="flex w-full items-center gap-2 px-4 py-2.5 text-left text-sm font-semibold text-red-600 hover:bg-paper dark:text-red-400" role="menuitem">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M15 17l5-5-5-5M20 12H9M12 21H5a2 2 0 01-2-2V5a2 2 0 012-2h7"/></svg>
                                Αποσύνδεση
                            </button>
                        </form>
                    </div>
                </div>
            @else
                <a href="{{ route('login') }}" class="hidden sm:inline-flex px-3 py-2.5 text-sm font-semibold text-slate hover:text-ink">Σύνδεση</a>
            @endauth
            <a href="{{ route('quote.builder') }}" class="inline-flex shrink-0 items-center whitespace-nowrap rounded-full bg-onair px-3.5 sm:px-5 py-2.5 text-sm font-semibold text-white hover:bg-onair-dark">
                <span class="sm:hidden">Προσφορά</span><span class="max-sm:hidden">Βγάλτε προσφορά</span>
            </a>
            @auth
                <a href="{{ route('account') }}" class="sm:hidden grid h-9 w-9 shrink-0 place-items-center rounded-full bg-onair text-xs font-extrabold text-white"
                   aria-label="Το Studio μου ({{ auth()->user()->business?->displayName() ?? auth()->user()->name }})">{{ mb_strtoupper(mb_substr(auth()->user()->business?->displayName() ?? auth()->user()->name, 0, 1)) }}</a>
            @endauth
            <button type="button" class="lg:hidden p-2 -mr-2 rounded-md" @click="open = !open" :aria-expanded="open" aria-controls="mobile-nav">
                <span class="sr-only">Μενού</span>
                <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path x-show="!open" d="M4 7h16M4 12h16M4 17h16"/><path x-show="open" x-cloak d="M6 6l12 12M18 6L6 18"/></svg>
            </button>
        </div>
    </div>

    <nav id="mobile-nav" x-show="open" x-cloak x-transition.opacity class="lg:hidden border-t border-line bg-surface" aria-label="Μενού κινητού">
        <div class="px-4 py-3 flex flex-col">
            @foreach ($nav as [$label, $href])
                <a href="{{ $href }}" @click="open = false" class="py-3 text-lg font-medium border-b border-line last:border-0">{{ $label }}</a>
            @endforeach
            @auth
                <div class="mt-3 rounded-2xl bg-paper px-4 py-3">
                    <p class="text-xs text-slate">Συνδεδεμένος ως</p>
                    <p class="font-semibold">{{ auth()->user()->business?->displayName() ?? auth()->user()->name }}</p>
                    <p class="text-sm text-slate">{{ auth()->user()->email }}</p>
                </div>
                <a href="{{ route('account') }}" class="py-3 text-lg font-medium border-b border-line">Το Studio μου</a>
                <a href="{{ route('quotes.index') }}" class="py-3 text-lg font-medium border-b border-line">Οι προσφορές μου</a>
                <a href="{{ route('account.edit') }}" class="py-3 text-lg font-medium border-b border-line">Στοιχεία επιχείρησης</a>
                <form method="POST" action="{{ route('logout') }}">@csrf
                    <button class="w-full py-3 text-left text-lg font-semibold text-red-600 dark:text-red-400">Αποσύνδεση</button>
                </form>
            @else
                <a href="{{ route('login') }}" class="py-3 text-lg font-medium">Σύνδεση</a>
                <a href="{{ route('register') }}" class="mt-2 text-center rounded-full border border-line px-5 py-3 font-semibold">Δωρεάν εγγραφή επιχείρησης</a>
            @endauth
        </div>
    </nav>
</header>
