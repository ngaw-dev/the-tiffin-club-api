<?php

// config/clerk.php

return [
    'issuer' => env('CLERK_ISSUER'),

    'jwks_url' => env('CLERK_JWKS_URL', rtrim((string) env('CLERK_ISSUER'), '/').'/.well-known/jwks.json'),

    'allowed_origins' => array_filter(array_map('trim', explode(',', (string) env('CLERK_ALLOWED_ORIGINS', '')))),
];
