<?php

namespace App\Http\Controllers\User;

use App\Facades\ActivityLog;
use App\Http\Controllers\Controller;
use App\Http\Requests\User\UserAuthRequest;
use App\Models\SystemLog;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class UserAuthController extends Controller
{

    public function auth(UserAuthRequest $request): JsonResponse
    {
        if ($request->validated()) {

            $credentials = $request->only('email', 'password');
            $email = $credentials['email'];

            // Verifica se o usuário existe antes de tentar autenticar
            $user = User::where('email', $email)->first();

            if (!$user) {
                // Log de tentativa de login com email não encontrado
                ActivityLog::logLoginFailed(
                    email: $email,
                    reason: 'Email não cadastrado no sistema'
                );

                return response()->json([
                    'message' => 'Email ou senha inválidos',
                ], 401);
            }

            $tokens = Auth::guard('auth')->attempt($credentials);

            if ($tokens) {
                $id = Auth::guard('auth')->id();
                $authenticatedUser = User::query()
                    ->where('id', $id)
                    ->first();

                // Log de login bem-sucedido
                ActivityLog::logLogin(
                    user: $authenticatedUser,
                    description: "Login realizado com sucesso via API: {$authenticatedUser->name} ({$authenticatedUser->email})"
                );

                return response()->json([
                    'message' => 'Authenticated',
                    'data' => [
                        'access_token' => $tokens['access_token'],
                        'access_token_expires_in' => $tokens['access_token_expires_in'],
                        'refresh_token' => $tokens['refresh_token'],
                        'user' => $authenticatedUser->getFullInfo()
                    ],
                ]);
            } else {
                // Log de tentativa de login com senha incorreta
                ActivityLog::logLoginFailed(
                    email: $email,
                    reason: 'Senha incorreta'
                );

                return response()->json([
                    'message' => 'Email ou senha inválidos',
                ], 401);
            }
        }

        // Log de tentativa de autenticação com dados inválidos
        ActivityLog::log(
            action: 'auth_validation_failed',
            description: "Tentativa de autenticação com dados de validação inválidos",
            level: SystemLog::LEVEL_WARNING,
            module: 'auth',
            data: [
                'metadata' => [
                    'validation_errors' => $request->errors()->toArray()
                ]
            ]
        );

        return response()->json(['message' => 'Unauthorized'], 401);
    }

    public function refresh(): JsonResponse
    {
        $user = Auth::guard('auth')->user();

        // Verifica se está autenticado antes de tentar refresh
        if (!$user) {
            // Log de tentativa de refresh sem autenticação
            ActivityLog::log(
                action: 'token_refresh_failed',
                description: "Tentativa de refresh de token sem autenticação válida",
                level: SystemLog::LEVEL_WARNING,
                module: 'auth'
            );

            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $tokens = Auth::guard('auth')->attempt();

        if ($tokens) {
            // Log de refresh de token bem-sucedido
            ActivityLog::log(
                action: 'token_refreshed',
                description: "Token renovado com sucesso: {$user->name} ({$user->email})",
                level: SystemLog::LEVEL_INFO,
                module: 'auth',
                model: $user
            );

            return response()->json([
                'message' => 'Token refreshed',
                'data' => $tokens
            ]);
        }

        // Log de falha no refresh do token
        ActivityLog::log(
            action: 'token_refresh_failed',
            description: "Falha ao renovar token: {$user->name} ({$user->email})",
            level: SystemLog::LEVEL_WARNING,
            module: 'auth',
            model: $user
        );

        return response()->json(['message' => 'Unauthorized'], 401);
    }

    public function logout(): JsonResponse
    {
        if (Auth::guard('auth')->check()) {
            $user = Auth::guard('auth')->user();

            // Log de logout antes de deslogar (para ter os dados do usuário)
            ActivityLog::logLogout(
                user: $user,
                description: "Logout realizado via API: {$user->name} ({$user->email})"
            );

            Auth::guard('auth')->logout();

            return response()->json(['message' => 'Logged out']);
        }

        // Log de tentativa de logout sem estar autenticado
        ActivityLog::log(
            action: 'logout_failed',
            description: "Tentativa de logout sem autenticação válida",
            level: SystemLog::LEVEL_DEBUG,
            module: 'auth'
        );

        return response()->json([
            'message' => 'Unauthorized',
        ], 401);
    }

}