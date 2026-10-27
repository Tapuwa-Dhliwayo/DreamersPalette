<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $title ?? 'Writing Studio' }} — Dreamer’s Palette</title>
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    @vite(['resources/css/studio.css', 'resources/js/studio.js'])
    @livewireStyles
</head>
<body class="studio-body">
    <a href="#studio-main" class="studio-skip">Skip to writing</a>
    <div class="studio-shell">
        {{ $slot }}
    </div>
    @livewireScriptConfig
    @fluxScripts
</body>
</html>
