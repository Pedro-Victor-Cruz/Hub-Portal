<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AuthMiddleware
{

    /**
     * Handle an incoming request.
     *
     * @param Closure(Request): (Response) $next
     */
    public function handle($request, Closure $next): Response
    {
        if (!Auth::guard('auth')->check())
            return response()->json([
                'message' => 'Unauthorized'
            ], 401);
        return $next($request);

    }

}
