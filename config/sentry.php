<?php

return [
    // The Laravel project DSN from the self-hosted Bugsink dashboard.
    'dsn' => env('SENTRY_LARAVEL_DSN'),

    'environment' => env('APP_ENV', 'production'),

    // Disable tracing by default to keep Bugsink and the VPS lightweight.
    'traces_sample_rate' => (float) env('SENTRY_TRACES_SAMPLE_RATE', 0),

    // Do not attach the authenticated user's identity by default.
    'send_default_pii' => false,
];
