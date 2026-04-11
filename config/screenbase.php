<?php

return [
    'catalog_only' => (bool) env('SCREENBASE_CATALOG_ONLY', false),

    'legacy_import_pipeline_enabled' => (bool) env('SCREENBASE_LEGACY_IMPORT_PIPELINE_ENABLED', false),

    'shell' => [
        'admin_shortcuts_enabled' => (bool) env('SCREENBASE_ADMIN_SHORTCUTS_ENABLED', false),
        'auth_shortcuts_enabled' => (bool) env('SCREENBASE_AUTH_SHORTCUTS_ENABLED', false),
        'watchlist_shortcuts_enabled' => (bool) env('SCREENBASE_WATCHLIST_SHORTCUTS_ENABLED', false),
    ],
];
