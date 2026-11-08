<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Feature paused — Dreamer’s Palette</title>
    <style>
        body { margin: 0; background: #1c1917; color: #fafaf9; font: 1rem/1.6 system-ui, sans-serif; }
        main { max-width: 42rem; margin: 4rem auto; padding: 1.5rem; }
        [data-flux-callout] { padding: 1.25rem; border: 1px solid #a16207; border-radius: .5rem; background: #292018; }
        svg { width: 1.25rem; height: 1.25rem; }
        h2 { margin-top: .5rem; font-size: 1.25rem; }
        a { display: inline-flex; align-items: center; gap: .5rem; margin-top: 1rem; color: #fde68a; }
    </style>
</head>
<body class="studio-body">
    <main class="mx-auto max-w-2xl p-8">
        <flux:callout variant="warning" icon="exclamation-triangle">
            <flux:callout.heading>This feature is paused</flux:callout.heading>
            <flux:callout.text>{{ $notice['message'] }} @if($notice['id']) Reference: {{ $notice['id'] }}.@endif</flux:callout.text>
        </flux:callout>
        <flux:button href="/dashboard" icon="arrow-left" class="mt-4">Back to dashboard</flux:button>
    </main>
</body>
</html>
