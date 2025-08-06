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
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $keyCompany = $request->header('Chave');
        /** @var User $auth */
        $auth = Auth::guard('auth')->user();

        if (empty($keyCompany)) {
            return response()->json([
                'message' => 'A chave de empresa é obrigatória no cabeçalho da requisição.'
            ], Response::HTTP_FORBIDDEN);
        }

        $useCache = !$request->headers->has('No-Cache') || $request->headers->get('No-Cache') !== 'S';

        $company = $useCache ?
            cache()->remember("company_api_key_{$keyCompany}", now()->addMinutes(5), function () use ($keyCompany) {
                return Company::where('chave', $keyCompany)->first();
            }) :
            Company::where('chave', $keyCompany)->first();

        if (!$company) {
            return response()->json([
                'message' => 'A chave de empresa fornecida é inválida.'
            ], Response::HTTP_FORBIDDEN);
        }

        if (!$auth->isAdmin() && (!$auth->empresa_id || $auth->empresa_id != $company->id)) {
            return response()->json([
                'message' => 'A chave de empresa fornecida não pertence à empresa do usuário autenticado.'
            ], Response::HTTP_FORBIDDEN);
        }

        // Adiciona a empresa autenticada ao request para fácil acesso nos controladores
        $request->merge([
            'company' => $company,
        ]);

        return $next($request);
    }
}
