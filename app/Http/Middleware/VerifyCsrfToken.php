<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        // Midtrans webhook routes - MUST be excluded from CSRF protection
        'checkout/payment-notification',        // Primary webhook URL
        'checkout/payment/notification',        // Alternative webhook URL
        'midtrans/notification',               // Backup webhook URL
        'webhook/midtrans',                    // Backup webhook URL
        'api/midtrans/webhook',               // API webhook URL
        
        // Test endpoints (for debugging)
        'api/payment/test-webhook',
        
        // Other webhook endpoints if needed in future
        'webhook/*',                          // Wildcard for all webhook routes
        'api/webhook/*',                      // API webhook routes
        
        // Additional patterns for safety
        '*/payment-notification',             // Any path ending with payment-notification
        '*/notification',                     // Any notification endpoint
        'api/webhooks/ginee/*',
    ];
}