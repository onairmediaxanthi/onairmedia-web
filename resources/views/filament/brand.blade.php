@php($logo = \App\Models\Setting::url('logo_light'))
@php($logoDark = \App\Models\Setting::url('logo_dark'))
@if ($logo)
    <img src="{{ $logo }}" alt="On Air Media Group" class="h-10 w-auto dark:hidden">
    <img src="{{ $logoDark ?: $logo }}" alt="On Air Media Group" class="h-10 w-auto hidden dark:block" @unless($logoDark) style="filter: invert(1) hue-rotate(180deg) saturate(1.3)" @endunless>
@else
    <span class="text-lg font-bold">On Air Media — Διαχείριση</span>
@endif
