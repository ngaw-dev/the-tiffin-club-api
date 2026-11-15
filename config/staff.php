<?php

return [
    // Staff access is by email allowlist; no admin DB flag.
    'emails' => array_filter(explode(',', (string) env('ADMIN_EMAILS', 'staff@admin.com'))),
];
