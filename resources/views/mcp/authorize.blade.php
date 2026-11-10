<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="robots" content="noindex, nofollow">
    <title>Connect {{ $client->name }} — Dreamer’s Palette</title>
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    @vite(['resources/css/studio.css'])
</head>
<body class="studio-body" style="--studio-atmosphere: url('{{ asset('assets/global_atmosphere.png') }}')">
<div class="studio-shell">
    <main id="studio-main" class="manuscript-library account-settings mcp-authorize">
        <h1 class="studio-page-title">Connect {{ $client->name }}</h1>
        <p>Signed in as <strong>{{ $user->email }}</strong>.</p>
        <p><strong>{{ $client->name }}</strong> will get read access to the material you select under Account, External access. Nothing can be changed through this connection, and you can disconnect it there at any time.</p>
        <p class="mcp-authorize-meta">Returns to {{ request()->query('redirect_uri', $client->redirect_uris[0] ?? 'the client') }}</p>
        <div class="studio-form-actions mcp-authorize-actions">
            <form method="POST" action="{{ route('passport.authorizations.deny') }}">
                @csrf
                @method('DELETE')
                <input type="hidden" name="state" value="{{ $request->state }}">
                <input type="hidden" name="client_id" value="{{ $client->id }}">
                <input type="hidden" name="auth_token" value="{{ $authToken }}">
                <button type="submit" class="mcp-authorize-button">Cancel</button>
            </form>
            <form method="POST" action="{{ route('passport.authorizations.approve') }}">
                @csrf
                <input type="hidden" name="state" value="{{ $request->state }}">
                <input type="hidden" name="client_id" value="{{ $client->id }}">
                <input type="hidden" name="auth_token" value="{{ $authToken }}">
                <button type="submit" class="mcp-authorize-button mcp-authorize-primary">Allow read access</button>
            </form>
        </div>
    </main>
</div>
</body>
</html>
