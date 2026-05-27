<?php

/**
 * API authentication middleware.
 * Validates and authenticates API requests.
 */

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;

class ApiAuthenticate extends Middleware
{
    protected function redirectTo($request): ?string
    {
        return null; // IMPORTANT: prevents HTML redirect /login crash
    }
}
