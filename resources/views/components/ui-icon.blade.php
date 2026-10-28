@props(['name' => 'star'])
<svg {{ $attributes->merge(['class' => 'h-6 w-6']) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
@switch($name)
    @case('radio')<rect x="3" y="8" width="18" height="12" rx="2.5"/><path d="M7 8l9-4"/><circle cx="8.5" cy="14" r="2.5"/><path d="M14 12.5h4M14 15.5h4"/>@break
    @case('news')<path d="M4 5h13v14H6a2 2 0 01-2-2V5z"/><path d="M17 9h3v8a2 2 0 01-2 2h-1"/><path d="M7.5 8.5h6M7.5 12h6M7.5 15.5h4"/>@break
    @case('screen')<rect x="2.5" y="4" width="19" height="12" rx="1.5"/><path d="M8 20h8M12 16v4"/><path d="M6 8h3M6 11h6" opacity=".7"/>@break
    @case('chart')<path d="M4 20V10M10 20V4M16 20v-7M22 20H2"/>@break
    @case('mic')<rect x="9" y="3" width="6" height="11" rx="3"/><path d="M5 11a7 7 0 0014 0M12 18v3M8.5 21h7"/>@break
    @case('megaphone')<path d="M3 10v4a1 1 0 001 1h2l5 4V5L6 9H4a1 1 0 00-1 1z"/><path d="M15 9a4 4 0 010 6M18 6a8 8 0 010 12"/>@break
    @case('users')<circle cx="9" cy="8" r="3.2"/><path d="M3 20a6 6 0 0112 0"/><circle cx="17" cy="9" r="2.5"/><path d="M16 14.2a5 5 0 015 5.8"/>@break
    @case('phone')<rect x="6.5" y="2.5" width="11" height="19" rx="2.5"/><path d="M11 18.5h2"/>@break
    @case('map')<path d="M9 4L3 6.5v13L9 17l6 2.5 6-2.5v-13L15 6.5 9 4z"/><path d="M9 4v13M15 6.5v13"/>@break
    @case('download')<path d="M12 3v12M7 10l5 5 5-5M4 19h16"/>@break
    @case('mail')<rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3.5 6.5L12 13l8.5-6.5"/>@break
    @case('clock')<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>@break
    @case('pin')<path d="M12 21s-7-6.1-7-11.5a7 7 0 0114 0C19 14.9 12 21 12 21z"/><circle cx="12" cy="9.5" r="2.5"/>@break
    @case('check')<path d="M5 12.5l4.5 4.5L19 7.5"/>@break
    @case('arrow')<path d="M5 12h14M13 6l6 6-6 6"/>@break
    @case('play')<path d="M8 5.5v13l11-6.5-11-6.5z" fill="currentColor" stroke="none"/>@break
    @default<path d="M12 3l2.6 5.6 6.1.7-4.5 4.2 1.2 6L12 16.8 6.6 19.5l1.2-6L3.3 9.3l6.1-.7L12 3z"/>
@endswitch
</svg>
