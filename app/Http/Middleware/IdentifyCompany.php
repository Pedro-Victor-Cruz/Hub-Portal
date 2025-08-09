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

        // Pega chave do header ou query param
        $keyCompany = $request->header('Chave') ?? $request->query('company_key');

        /** @var User $auth */
        $auth = Auth::guard('auth')->user();

        if (empty($keyCompany)) {
            if ($required) {
                return response()->json([
                    'message' => 'A chave de empresa é obrigatória (informe no cabeçalho "Chave" ou query param "company_key").'
                ], Response::HTTP_FORBIDDEN);
            }

            // Se não é obrigatório, apenas segue
            return $next($request);
        }

        // Controle de cache
        $useCache = !$request->headers->has('No-Cache') || $request->headers->get('No-Cache') !== 'S';

        $company = $useCache
            ? cache()->remember("company_api_key_{$keyCompany}", now()->addMinutes(5), function () use ($keyCompany) {
                return Company::where('key', $keyCompany)->first();
            })
            : Company::where('key', $keyCompany)->first();

        if (!$company) {
            return response()->json([
                'message' => 'A chave de empresa fornecida é inválida.'
            ], Response::HTTP_FORBIDDEN);
        }

        // Valida se o usuário tem acesso
        if (!$auth->isAdmin() && (!$auth->company_id || $auth->company_id != $company->id)) {
            return response()->json([
                'message' => 'A chave de empresa fornecida não pertence à empresa do usuário autenticado.'
            ], Response::HTTP_FORBIDDEN);
        }

        // Injeta a empresa no request
        $request->merge([
            'company' => $company,
        ]);

        return $next($request);
    }
}
