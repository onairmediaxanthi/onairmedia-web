@props(['onDark' => false])
@php
    $light = \App\Models\Setting::url('logo_light');
    $dark = \App\Models\Setting::url('logo_dark');
    $name = \App\Models\Setting::get('company_name', 'On Air Media Group');
@endphp
@if ($light)
    @if ($onDark)
        {{-- Πάντα σκούρο φόντο (footer, player) --}}
        <img src="{{ $dark ?: $light }}" alt="{{ $name }}" {{ $attributes->class(['w-auto', 'logo-auto-always' => ! $dark]) }} decoding="async">
    @elseif ($dark)
        <img src="{{ $light }}" alt="{{ $name }}" {{ $attributes->class(['w-auto dark:hidden']) }} decoding="async">
        <img src="{{ $dark }}" alt="{{ $name }}" {{ $attributes->class(['w-auto hidden dark:block']) }} decoding="async">
    @else
        <img src="{{ $light }}" alt="{{ $name }}" {{ $attributes->class(['w-auto logo-auto']) }} decoding="async">
    @endif
@else
    <span {{ $attributes->class(['font-extrabold tracking-tight text-lg']) }}>{{ $name }}</span>
@endif
