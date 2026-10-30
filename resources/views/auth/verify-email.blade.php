<x-layouts.site title="Επιβεβαίωση email — On Air Media Group">
    <x-auth-card title="Ελέγξτε το email σας" intro="Στείλαμε σύνδεσμο επιβεβαίωσης στο {{ auth()->user()->email }}. Πατήστε τον και ο λογαριασμός σας ενεργοποιείται.">
        <ul class="space-y-2 text-[15px] text-slate">
            <li>Δεν το βλέπετε; Κοιτάξτε στα «Ανεπιθύμητα» ή «Προωθήσεις».</li>
            <li>Ο σύνδεσμος ισχύει 24 ώρες.</li>
        </ul>
        <form method="POST" action="{{ route('verification.send') }}" class="mt-6">
            @csrf
            <x-form.button class="w-full">Στείλτε ξανά τον σύνδεσμο</x-form.button>
        </form>
        <form method="POST" action="{{ route('logout') }}" class="mt-4 text-center">
            @csrf
            <button type="submit" class="text-[15px] font-semibold text-slate hover:text-ink">Αποσύνδεση</button>
        </form>
    </x-auth-card>
</x-layouts.site>
