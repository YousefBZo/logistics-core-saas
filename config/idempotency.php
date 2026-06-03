<?php

return [
    'store' => env('IDEMPOTENCY_CACHE_STORE', 'redis'),
    'ttl_seconds' => (int) env('IDEMPOTENCY_TTL_SECONDS', 60),
    'lock_seconds' => (int) env('IDEMPOTENCY_LOCK_SECONDS', 30),
];
