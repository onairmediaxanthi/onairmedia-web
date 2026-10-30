<x-layouts.site title="Νέος κωδικός — On Air Media Group">
    <x-auth-card title="Νέος κωδικός">
        <form method="POST" action="{{ route('password.update') }}" class="space-y-5">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <x-form.field name="email" label="Email" type="email" required autocomplete="email" :value="$email" />
            <x-form.field name="password" label="Νέος κωδικός" type="password" required autocomplete="new-password" hint="Τουλάχιστον 10 χαρακτήρες, με κεφαλαία, μικρά και αριθμό." />
            <x-form.field name="password_confirmation" label="Ξανά ο νέος κωδικός" type="password" required autocomplete="new-password" />
            <x-form.button class="w-full">Αποθήκευση κωδικού</x-form.button>
        </form>
    </x-auth-card>
</x-layouts.site>
