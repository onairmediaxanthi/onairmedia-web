@props(['title' => null, 'description' => null, 'ogImage' => null, 'canonical' => null])
<!doctype html>
<html lang="el">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'On Air Media Group — Ραδιόφωνα, ειδήσεις και διαφήμιση στη Θράκη' }}</title>
    <meta name="description" content="{{ $description ?? 'Δώδεκα ραδιόφωνα, το thrakitoday.gr και η Οθόνη Πόλης. Ακούστε ζωντανά και διαφημίστε την επιχείρησή σας σε όλη την Ξάνθη.' }}">
    <link rel="canonical" href="{{ $canonical ?? url()->current() }}">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="On Air Media Group">
    <meta property="og:title" content="{{ $title ?? 'On Air Media Group' }}">
    <meta property="og:description" content="{{ $description ?? 'Δώδεκα ραδιόφωνα, το thrakitoday.gr και η Οθόνη Πόλης.' }}">
    <meta property="og:url" content="{{ $canonical ?? url()->current() }}">
    @isset($ogImage)<meta property="og:image" content="{{ $ogImage }}">@endisset
    <meta name="theme-color" content="#121a33">
    @if ($favicon = \App\Models\Setting::url('favicon'))
        <link rel="icon" href="{{ $favicon }}">
    @else
        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" type="image/png" sizes="32x32" href="/icons/onair-32.png">
    @endif
    <link rel="apple-touch-icon" sizes="180x180" href="/icons/onair-180.png">
    <link rel="manifest" href="/site.webmanifest">
    <meta name="color-scheme" content="light dark">
    {{-- Πριν τα styles: χωρίς «αναλαμπή» λάθος θέματος --}}
    <script>
        (function () {
            var t = null;
            try { t = localStorage.getItem('onair:theme'); } catch (e) {}
            document.documentElement.classList.add('js-reveal');
            var dark = t ? t === 'dark' : window.matchMedia('(prefers-color-scheme: dark)').matches;
            document.documentElement.classList.toggle('dark', dark);
        })();
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    @stack('head')
</head>
<body class="min-h-dvh pb-24">
    <a href="#main" class="sr-only focus:not-sr-only focus:fixed focus:top-3 focus:left-3 focus:z-50 focus:bg-white focus:px-4 focus:py-2 focus:rounded-md">Μετάβαση στο περιεχόμενο</a>

    @include('partials.header')

    <main id="main">
        {{ $slot }}
    </main>

    @include('partials.footer')
    @include('partials.callback')

    {{-- Ο player ζει έξω από τη ροή των σελίδων: δεν ξαναφορτώνεται ποτέ --}}
    @persist('player')
        <script type="application/json" id="stations-data">@json($playerStations)</script>
        @include('partials.player')
    @endpersist

    @livewireScripts
</body>
</html>
