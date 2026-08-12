<?php

// Restricts cross-origin access to the API to Admin Desktop's known Tauri
// webview origins. Deliberately not "*" — see docs/architecture.md security
// notes. Extend CORS_ALLOWED_ORIGINS (not this file) when new local clients
// (e.g. a future LAN device) need access.

return [

    'paths' => ['api/*'],

    'allowed_methods' => ['*'],

    'allowed_origins' => array_filter(array_map(
        'trim',
        explode(',', env('CORS_ALLOWED_ORIGINS', 'tauri://localhost'))
    )),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,

];
