<?php

return [
    'local_mcp_enabled' => env('APP_ENV', 'production') === 'local' && env('STUDIO_LOCAL_MCP_ENABLED', true),
    'node_binary' => env('BROWSERSHOT_NODE_BINARY', env('NODE_BINARY', 'node')),
    'chrome_path' => env('BROWSERSHOT_CHROME_PATH'),
];
