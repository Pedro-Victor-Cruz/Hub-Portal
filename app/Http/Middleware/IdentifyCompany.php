<?php

namespace App\Http\Middleware;

use App\Models\Company;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class IdentifyCompany
{

    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @param  bool|string  $required  // 'true' ou 'false'
     * @return Response
     */
    public function handle(Request $request, Closure $next, $required = 'true'): Response
    {
        $required = filter_var($required, FILTER_VALIDATE_BOOLEAN);

        /** @var User $auth */
        $auth = Auth::guard('auth')->user();

        // Pega chave ou id de empresa seguindo a prioridade:
        // Header 'Chave' → query 'company_key' → query 'company_id'
        $keyCompany = $request->header('Chave')
            ?? $request->query('company_key')
            ?? $request->query('company_id');

        if (empty($keyCompany)) {
            if ($required) {
                return response()->json([
                    'message' => 'A chave ou ID de empresa é obrigatória (informe no cabeçalho "Chave", query param "company_key" ou "company_id").'
                ], Response::HTTP_FORBIDDEN);
            }

            // Se não é obrigatório, apenas segue
            return $next($request);
        }

        // Controle de cache
        $useCache = !$request->headers->has('No-Cache') || $request->headers->get('No-Cache') !== 'S';

        // Busca a empresa pela chave ou ID
        $company = $useCache
            ? cache()->remember("company_api_key_{$keyCompany}", now()->addMinutes(5), function () use ($keyCompany) {
                return Company::where('key', $keyCompany)
                    ->orWhere('id', $keyCompany)
                    ->first();
            })
            : Company::where('key', $keyCompany)
                ->orWhere('id', $keyCompany)
                ->first();

        if (!$company) {
            return response()->json([
                'message' => 'A chave ou ID de empresa fornecida é inválida.'
            ], Response::HTTP_FORBIDDEN);
        }

        // Valida se o usuário tem acesso
        if (!$auth->isAdmin() && (!$auth->company_id || $auth->company_id != $company->id)) {
            return response()->json([
                'message' => 'A empresa fornecida não pertence ao usuário autenticado.'
            ], Response::HTTP_FORBIDDEN);
        }

        // Injeta a empresa no request
        $request->merge([
            'company' => $company,
        ]);

        return $next($request);
    }

}
