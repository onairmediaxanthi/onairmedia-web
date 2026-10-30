<x-layouts.site title="Ενεργοποίηση λογαριασμού — On Air Media Group">
    <x-auth-card title="Ενεργοποίηση λογαριασμού">
        <p class="text-slate">Για την επιχείρηση <strong class="text-ink">{{ $business->displayName() }}</strong> · {{ $user->email }}</p>
        <form method="POST" action="{{ request()->fullUrl() }}" class="mt-6 space-y-5">
            @csrf
            <x-form.field name="password" label="Κωδικός" type="password" required autocomplete="new-password" hint="Τουλάχιστον 10 χαρακτήρες, με κεφαλαία, μικρά και αριθμό." />
            <x-form.field name="password_confirmation" label="Ξανά ο κωδικός" type="password" required autocomplete="new-password" />
            <label class="flex items-start gap-3">
                <input type="checkbox" name="terms" value="1" required class="mt-1 h-5 w-5 accent-[var(--color-onair)]" @checked(old('terms'))>
                <span>Αποδέχομαι τους <a href="{{ route('legal.terms') }}" target="_blank" class="font-semibold text-onair hover:underline">όρους χρήσης</a> και έχω διαβάσει την <a href="{{ route('legal.privacy') }}" target="_blank" class="font-semibold text-onair hover:underline">ενημέρωση για τα προσωπικά δεδομένα</a>.</span>
            </label>
            @error('terms')<p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
            <label class="flex items-start gap-3">
                <input type="checkbox" name="marketing" value="1" class="mt-1 h-5 w-5 accent-[var(--color-onair)]" @checked(old('marketing'))>
                <span class="text-slate">Θέλω να λαμβάνω προσφορές και νέα στο email μου (προαιρετικό).</span>
            </label>
            <x-form.button class="w-full">Ενεργοποίηση</x-form.button>
        </form>
    </x-auth-card>
</x-layouts.site>
