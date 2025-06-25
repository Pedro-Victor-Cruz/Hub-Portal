<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PaginationMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        // Verifica se a requisição é para uma API e se é GET
        if ($request->is('api/*') && $request->isMethod('get')) {
            // Configura o padrão de paginação se não estiver definido
            if (!$request->has('per_page')) {
                $perPage = $request->header('X-Paginate', config('api.pagination.per_page'));
                $request->merge(['per_page' => $perPage]);
            }
        }

        return $next($request);
    }
}
