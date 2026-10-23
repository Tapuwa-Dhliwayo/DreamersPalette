<?php

return [
    'source_path' => env('SUPABASE_MIGRATION_SOURCE_PATH', storage_path('app/private/migration-source')),
    'source_url' => env('SUPABASE_MIGRATION_SOURCE_URL', env('VITE_SUPABASE_URL')),
];
