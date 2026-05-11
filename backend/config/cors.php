<?php

/*
 * Cross-Origin Resource Sharing (CORS) configuration.
 *
 * Allowed origins are driven by env so the same image can be promoted across
 * environments. CORS_ALLOWED_ORIGINS is a comma-separated list, e.g.:
 *     CORS_ALLOWED_ORIGINS=https://kabinet-hris.vercel.app,https://hris.kabinet.sr
 *
 * Auth between Vercel-portal and Fly-backend is Bearer-token (see
 * frontend/src/lib/api.ts), so supports_credentials is intentionally false.
 */

$origins = array_filter(array_map('trim', explode(',', (string) env('CORS_ALLOWED_ORIGINS', ''))));
$patterns = array_filter(array_map('trim', explode(',', (string) env('CORS_ALLOWED_ORIGIN_PATTERNS', ''))));

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie', 'storage/*', 'up'],

    'allowed_methods' => ['*'],

    'allowed_origins' => $origins ?: ['*'],

    'allowed_origins_patterns' => $patterns,

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 3600,

    'supports_credentials' => filter_var(env('CORS_SUPPORTS_CREDENTIALS', false), FILTER_VALIDATE_BOOLEAN),
];
