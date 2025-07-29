<?php

return [
    'enabled' => env('GITHUB_ISSUES_ENABLED', true),
    
    'github' => [
        'token' => env('GITHUB_TOKEN'),
        'owner' => env('GITHUB_OWNER'),
        'repository' => env('GITHUB_REPO'),
    ],
    
    'monitoring' => [
        'log_file' => storage_path('logs/laravel.log'),
        'poll_interval' => env('GITHUB_ISSUES_POLL_INTERVAL', 1),
        'buffer_size' => env('GITHUB_ISSUES_BUFFER_SIZE', 10),
        'deduplicate_timeout' => env('GITHUB_ISSUES_DEDUPE_TIMEOUT', 3600),
    ],
    
    'issue' => [
        'labels' => ['bug', 'auto-generated'],
        'assignees' => [],
        'title_prefix' => '[Auto] ',
        'include_stack_trace' => true,
        'include_request_info' => true,
    ],
    
    'filters' => [
        'min_level' => env('GITHUB_ISSUES_MIN_LEVEL', 'error'),
        'exclude_patterns' => [
            '/vendor/',
            'StreamHandler.php',
        ],
    ],
];