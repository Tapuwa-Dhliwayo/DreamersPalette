<?php

return [
    // Local CLI credentials never travel with an Anubis content refresh.
    'local_connections' => env('APP_ENV') === 'local',
    'codex_binary' => env('STUDIO_CODEX_BINARY', 'codex'),
    'claude_binary' => env('STUDIO_CLAUDE_BINARY', 'claude'),
    'timeout' => 300,
    'max_context_characters' => 180000,
];
