<footer class="mt-24 bg-night text-white/80">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 py-14 grid gap-10 md:grid-cols-3">
        <div>
            <x-site-logo on-dark class="h-12" />
            <p class="mt-4 max-w-xs text-white/70">{{ \App\Models\Setting::get('footer_text') }}</p>
            @php($s = \App\Models\Setting::allValues())
            <ul class="mt-4 space-y-1 text-white/80">
                @if ($s['phone'])<li><a class="hover:text-white" href="tel:{{ preg_replace('/[^0-9+]/', '', $s['phone']) }}">{{ $s['phone'] }}</a></li>@endif
                @if ($s['email'])<li><a class="hover:text-white" href="mailto:{{ $s['email'] }}">{{ $s['email'] }}</a></li>@endif
                @if ($s['address'])<li>{{ $s['address'] }}</li>@endif
            </ul>
        </div>
        <div>
            <p class="font-semibold text-white">Ακούστε και στο κινητό</p>
            <ul class="mt-3 space-y-2">
                <li><a class="hover:text-white underline-offset-4 hover:underline" href="https://apps.apple.com/us/app/on-air-media-group/id6762763280" rel="noopener" target="_blank">Εφαρμογή για iPhone</a></li>
                <li><a class="hover:text-white underline-offset-4 hover:underline" href="https://play.google.com/store/apps/details?id=gr.radio.onairmediagroup" rel="noopener" target="_blank">Εφαρμογή για Android</a></li>
            </ul>
            <p class="mt-3 text-sm text-white/60">Με Apple CarPlay και Android Auto στο αυτοκίνητο.</p>
        </div>
        <div>
            <p class="font-semibold text-white">Ειδήσεις</p>
            <p class="mt-3"><a class="hover:text-white underline-offset-4 hover:underline" href="https://thrakitoday.gr" rel="noopener" target="_blank">thrakitoday.gr</a></p>
        </div>
    </div>
    <div class="border-t border-white/10">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 py-6 flex flex-wrap gap-x-6 gap-y-2 text-sm text-white/50">
            <span>© {{ date('Y') }} {{ $s['company_name'] }}@if($s['vat']) · ΑΦΜ {{ $s['vat'] }}@endif @if($s['gemi']) · ΓΕΜΗ {{ $s['gemi'] }}@endif</span>
            <a href="{{ route('legal.terms') }}" class="hover:text-white">Όροι χρήσης</a>
            <a href="{{ route('legal.privacy') }}" class="hover:text-white">Προσωπικά δεδομένα</a>
            @foreach ((array) $s['socials'] as $net => $link)
                @if (is_string($link) && str_starts_with($link, 'https://'))
                    <a href="{{ $link }}" target="_blank" rel="noopener" class="hover:text-white capitalize">{{ $net }}</a>
                @endif
            @endforeach
        </div>
    </div>
</footer>
