<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Parallel Execution Defaults
    |--------------------------------------------------------------------------
    |
    | Controls the default parallel execution settings for the interactive
    | test runner. These can be overridden per-command via options.
    |
    */

    'parallel' => (bool) env('TEST_RUNNER_PARALLEL', true),

    'processes' => (int) env('TEST_RUNNER_PROCESSES', 4),

    'retry_attempts' => (int) env('TEST_RUNNER_RETRY_ATTEMPTS', 1),

    /*
    |--------------------------------------------------------------------------
    | Timeout & Backoff Configuration
    |--------------------------------------------------------------------------
    */

    'timeout' => (int) env('TEST_RUNNER_TIMEOUT', 300),

    'backoff' => [
        'base' => (int) env('TEST_RUNNER_BACKOFF_BASE', 5),
        'factor' => (float) env('TEST_RUNNER_BACKOFF_FACTOR', 2.0),
        'max_delay' => (int) env('TEST_RUNNER_BACKOFF_MAX', 60),
    ],

    /*
    |--------------------------------------------------------------------------
    | Reporting Configuration
    |--------------------------------------------------------------------------
    */

    'report' => [
        'output_file' => env('TEST_RUNNER_REPORT_FILE', 'test-report.md'),
        'format' => env('TEST_RUNNER_REPORT_FORMAT', 'markdown'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Discovery Configuration
    |--------------------------------------------------------------------------
    */

    'paths' => [
        'tests' => env('TEST_RUNNER_TEST_PATH', base_path('tests')),
        'reports' => env('TEST_RUNNER_REPORT_PATH', base_path()),
    ],

    'patterns' => [
        'unit' => env('TEST_RUNNER_PATTERN_UNIT', 'tests/Unit/*Test.php'),
        'feature' => env('TEST_RUNNER_PATTERN_FEATURE', 'tests/Feature/*Test.php'),
        'livewire' => env('TEST_RUNNER_PATTERN_LIVEWIRE', 'tests/Feature/Livewire/*Test.php'),
        'browser' => env('TEST_RUNNER_PATTERN_BROWSER', 'tests/Browser/*Test.php'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Environment Overrides
    |--------------------------------------------------------------------------
    |
    | Per-environment overrides applied using the current APP_ENV. Nested
    | configuration keys will replace the corresponding defaults above.
    |
    */

    'environments' => [
        'local' => [
            'parallel' => true,
            'processes' => 4,
        ],
        'ci' => [
            'parallel' => (bool) env('CI_PARALLEL', true),
            'processes' => (int) env('CI_PROCESSES', 6),
            'retry_attempts' => (int) env('CI_RETRY_ATTEMPTS', 0),
            'timeout' => (int) env('CI_TIMEOUT', 600),
        ],
    ],
];
