<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckPermission
{
    public function handle(Request $request, Closure $next, $permission)
    {
        $user = $request->user('auth');

        if ($user->isAdmin()) {
            return $next($request);
        }

        if (!$user->hasPermissionTo($permission)) {
            abort(403, 'Acesso não autorizado');
        }

        return $next($request);
    }
}
