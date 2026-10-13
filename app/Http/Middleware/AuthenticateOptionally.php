<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolves a Sanctum user (bearer token or first-party session) when one is present,
 * but lets guests through. Public endpoints use it to personalise precision and favorites.
 */
class AuthenticateOptionally
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::guard('sanctum')->check()) {
            Auth::shouldUse('sanctum');
        }

        return $next($request);
    }
}
