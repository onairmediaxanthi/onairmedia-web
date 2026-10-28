<x-layouts.site title="Διαγραφή από τις προσφορές — On Air Media Group">
    <section class="mx-auto max-w-xl px-4 sm:px-6 py-20 text-center">
        @if ($done)
            <h1 class="text-3xl font-extrabold tracking-tight">Έγινε</h1>
            <p class="mt-4 text-slate">{{ $test ? 'Αυτό ήταν δοκιμαστικό email· δεν άλλαξε τίποτα.' : 'Δεν θα λαμβάνετε πλέον email με προσφορές. Ο λογαριασμός σας και οι προσφορές σας μένουν κανονικά.' }}</p>
            @unless ($test)<p class="mt-2 text-sm text-slate">Αν αλλάξετε γνώμη, το ενεργοποιείτε ξανά από το Studio σας.</p>@endunless
        @else
            <h1 class="text-3xl font-extrabold tracking-tight">Διαγραφή από τις προσφορές</h1>
            <p class="mt-4 text-slate">Θέλετε να σταματήσετε να λαμβάνετε email με προσφορές στο <strong class="text-ink">{{ $recipient?->email }}</strong>;</p>
            <form method="POST" action="{{ request()->url() }}" class="mt-6">@csrf
                <x-form.button>Ναι, διαγραφή</x-form.button>
            </form>
        @endif
    </section>
</x-layouts.site>
