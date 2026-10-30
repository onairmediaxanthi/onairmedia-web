<x-layouts.site title="Υπενθυμίσεις προσφοράς — On Air Media Group">
    <section class="mx-auto max-w-xl px-4 sm:px-6 py-16 text-center">
        <h1 class="text-3xl font-extrabold tracking-tight">Υπενθυμίσεις για την προσφορά {{ $quote->number }}</h1>
        @if (session('status') || $quote->reminders_off)
            <p class="mt-4 text-slate">{{ session('status') ?? 'Οι υπενθυμίσεις για αυτή την προσφορά είναι σταματημένες.' }}</p>
        @else
            <p class="mt-4 text-slate">Θέλετε να σταματήσουν τα email υπενθύμισης για αυτή την προσφορά; Η προσφορά μένει στο Studio σας.</p>
            <form method="POST" action="{{ request()->fullUrl() }}" class="mt-6">@csrf
                <x-form.button>Ναι, σταματήστε τις</x-form.button>
            </form>
        @endif
    </section>
</x-layouts.site>
