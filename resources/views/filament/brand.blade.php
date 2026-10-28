@php($logo = \App\Models\Setting::url('logo_light'))
@php($logoDark = \App\Models\Setting::url('logo_dark'))
@if ($logo)
    {{-- Το Filament έχει δικό του CSS: η εναλλαγή φωτεινού/σκοτεινού γίνεται εδώ, αυτόνομα --}}
    <style>
        .oam-logo { height: 2.5rem; width: auto; }
        .oam-logo--dark { display: none; }
        .dark .oam-logo--dark { display: block; }
        .dark .oam-logo--light { display: none; }
        .dark .oam-logo--auto { filter: invert(1) hue-rotate(180deg) saturate(1.3); }
    </style>
    @if ($logoDark)
        <img src="{{ $logo }}" alt="On Air Media Group" class="oam-logo oam-logo--light">
        <img src="{{ $logoDark }}" alt="On Air Media Group" class="oam-logo oam-logo--dark">
    @else
        <img src="{{ $logo }}" alt="On Air Media Group" class="oam-logo oam-logo--auto">
    @endif
@else
    <span style="font-size: 1.125rem; font-weight: 700">On Air Media — Διαχείριση</span>
@endif
