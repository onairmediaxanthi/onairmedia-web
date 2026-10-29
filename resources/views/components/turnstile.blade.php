{{-- «Δεν είμαι ρομπότ» (Cloudflare Turnstile) για κλασικές φόρμες: εμφανίζεται μόνο όταν υπάρχουν και τα δύο κλειδιά --}}
@if (\App\Support\Turnstile::enabled())
    @once
        @push('head')
            <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
        @endpush
    @endonce
    <div>
        <div class="cf-turnstile" data-sitekey="{{ \App\Support\Turnstile::siteKey() }}" data-language="el"
             data-theme="auto"></div>
        @error('robot')<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
    </div>
@endif
