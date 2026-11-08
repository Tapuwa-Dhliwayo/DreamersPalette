<?php

return [
    // Local CLI credentials never travel with an Anubis content refresh.
    'local_user_id' => (int) env('STUDIO_LOCAL_AUTHOR_ID', 1),
    'local_connections' => env('APP_ENV') === 'local',
    'codex_binary' => env('STUDIO_CODEX_BINARY', 'codex'),
    'claude_binary' => env('STUDIO_CLAUDE_BINARY', 'claude'),
    'timeout' => 300,
    'concurrent_turns_per_user' => 2,
    'max_output_tokens' => 8192,
    'reading_window_characters' => 12000,
    'focused_reading_rounds' => 5,
    'whole_reading_rounds' => 24,
    'evidence_characters' => 70000,
    'max_context_characters' => 180000,
];
