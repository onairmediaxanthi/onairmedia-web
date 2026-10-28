<x-layouts.site title="Σύνδεση — On Air Media Group">
    <x-auth-card title="Σύνδεση" intro="Μπείτε στο Studio σας για τιμές, καμπάνιες και τιμολόγια.">
        <form method="POST" action="{{ route('login') }}" class="space-y-5">
            @csrf
            <x-form.field name="email" label="Email" type="email" required autocomplete="email" autofocus />
            <x-form.field name="password" label="Κωδικός" type="password" required autocomplete="current-password" />
            <div class="flex items-center justify-between gap-3">
                <label class="flex items-center gap-2 text-[15px]"><input type="checkbox" name="remember" value="1" class="h-4 w-4 rounded border-line text-onair focus:ring-onair"> Να με θυμάσαι</label>
                <a href="{{ route('password.request') }}" class="text-[15px] font-semibold text-onair hover:underline">Ξεχάσατε τον κωδικό;</a>
            </div>
            <x-form.button class="w-full">Σύνδεση</x-form.button>
        </form>
        <x-slot:footer>Νέος επαγγελματίας; <a href="{{ route('register') }}" class="font-semibold text-onair hover:underline">Δωρεάν εγγραφή</a></x-slot:footer>
    </x-auth-card>
</x-layouts.site>
