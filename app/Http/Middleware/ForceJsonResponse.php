<?php

/**
 * Middleware to force JSON responses.
 * Ensures API endpoints return JSON consistently.
 */

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ForceJsonResponse
{
    public function handle(Request $request, Closure $next): Response
    {
        // This forces every request to be treated as an API call
        $request->headers->set('Accept', 'application/json');

        return $next($request);
    }
}
