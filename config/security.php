<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Security Middleware Configuration
    |--------------------------------------------------------------------------
    */

    'rate_limit' => [
        // Maximum requests per IP per time window (only for unauthenticated users)
        'max_attempts' => env('SECURITY_RATE_LIMIT_MAX', 1000),
        
        // Time window in minutes
        'decay_minutes' => env('SECURITY_RATE_LIMIT_DECAY', 1),
    ],
];
