<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Patch System Enabled
    |--------------------------------------------------------------------------
    |
    | Enable or disable the patch system globally.
    |
    */
    'enabled' => env('PATCH_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Last Patch Timestamp
    |--------------------------------------------------------------------------
    |
    | Only files modified after this timestamp will be included in patches.
    | Format: Y-m-d H:i:s
    |
    */
    'last_patch_at' => env('LAST_PATCH_AT', '2026-03-30 19:28:58'),

    /*
    |--------------------------------------------------------------------------
    | Paths to Scan
    |--------------------------------------------------------------------------
    |
    | Directories to scan for modified files. Relative to base_path().
    | Add only the directories you want to track.
    |
    */
    'scan_paths' => [
        'app',
        'config',
        'resources',
        'routes',
        'database',
        'public',
    ],

    /*
    |--------------------------------------------------------------------------
    | Ignore Paths
    |--------------------------------------------------------------------------
    |
    | Paths containing these strings will be ignored during scanning.
    | Useful for excluding vendor, node_modules, cache files, etc.
    |
    */
    'ignore_paths' => [
        'vendor',
        'node_modules',
        'storage',
        'bootstrap/cache',
        '.git',
        '.env',
        '.idea',
        'patches',
        'patchs',
        // Frontend build directories (Vue/React)
        'public/build',
        'public/dist',
        'public/hot',
        'public/assets',
    ],

    /*
    |--------------------------------------------------------------------------
    | Ignore Extensions
    |--------------------------------------------------------------------------
    |
    | Files with these extensions will be ignored.
    |
    */
    'ignore_extensions' => [
        'log',
        'cache',
        'pyc',
        'swp',
    ],
];
