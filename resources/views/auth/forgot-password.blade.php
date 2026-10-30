<x-layouts.site title="Νέος κωδικός — On Air Media Group">
    <x-auth-card title="Ξεχάσατε τον κωδικό;" intro="Γράψτε το email σας και θα σας στείλουμε σύνδεσμο για νέο κωδικό.">
        <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
            @csrf
            <x-form.field name="email" label="Email" type="email" required autocomplete="email" autofocus />
            <x-turnstile />
            <x-form.button class="w-full">Αποστολή συνδέσμου</x-form.button>
        </form>
        <x-slot:footer><a href="{{ route('login') }}" class="font-semibold text-onair hover:underline">Πίσω στη σύνδεση</a></x-slot:footer>
    </x-auth-card>
</x-layouts.site>
