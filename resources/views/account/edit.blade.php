<x-layouts.site title="Στοιχεία επιχείρησης — On Air Media Group">
    <x-auth-card title="Στοιχεία επιχείρησης" intro="Χρησιμοποιούνται στα τιμολόγια και στην επικοινωνία για τις καμπάνιες σας." wide>
        <form method="POST" action="{{ route('account.update') }}" novalidate x-data="{ type: '{{ old('business_type', $business->business_type) }}' }" class="space-y-6">
            @csrf @method('PUT')
            <div class="grid gap-5 sm:grid-cols-2">
                <x-form.field name="legal_name" label="Επωνυμία" required :value="$business->legal_name" class="sm:col-span-2" />
                <x-form.field name="trade_name" label="Διακριτικός τίτλος" :value="$business->trade_name" />
                <div>
                    <label for="f-business_type" class="block text-sm font-semibold">Είδος επιχείρησης</label>
                    <select id="f-business_type" name="business_type" x-model="type" required class="mt-1.5 block w-full rounded-xl border border-line bg-surface px-4 py-3 text-[16px]">
                        @foreach (\App\Models\Business::TYPES as $k => $v)
                            <option value="{{ $k }}" @selected(old('business_type', $business->business_type) === $k)>{{ $v }}</option>
                        @endforeach
                    </select>
                </div>
                <div x-show="type === 'other'" x-cloak class="sm:col-span-2"><x-form.field name="business_type_other" label="Ποιο είδος;" required :value="$business->business_type_other" /></div>
                <x-form.field name="vat" label="ΑΦΜ" required inputmode="numeric" :value="$business->vat" />
                <x-form.field name="tax_office" label="ΔΟΥ" :value="$business->tax_office" />
                <x-form.field name="gemi" label="Αριθμός ΓΕΜΗ" inputmode="numeric" :value="$business->gemi" />
                <x-form.field name="website" label="Ιστοσελίδα" type="url" :value="$business->website" />
                <x-form.field name="address" label="Οδός και αριθμός" required :value="$business->address" class="sm:col-span-2" />
                <x-form.field name="city" label="Πόλη" required :value="$business->city" />
                <x-form.field name="postal_code" label="Τ.Κ." required inputmode="numeric" :value="$business->postal_code" />
                <x-form.field name="contact_person" label="Υπεύθυνος επικοινωνίας" required :value="$business->contact_person" class="sm:col-span-2" />
                <x-form.field name="mobile" label="Κινητό" required type="tel" :value="$business->mobile" />
                <x-form.field name="phone" label="Σταθερό" type="tel" :value="$business->phone" />
            </div>
            <div class="flex flex-wrap items-center gap-4">
                <x-form.button>Αποθήκευση</x-form.button>
                <a href="{{ route('account') }}" class="font-semibold text-slate hover:text-ink">Ακύρωση</a>
            </div>
        </form>
    </x-auth-card>
</x-layouts.site>
