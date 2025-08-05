<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\UserAuthRequest;
use App\Http\Requests\User\UserRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class UserAuthController extends Controller
{

    public function auth(UserAuthRequest $request): JsonResponse
    {

        if ($request->validated()) {

            $credentials = $request->only('email', 'password');
            $tokens = Auth::guard('auth')->attempt($credentials);
            if ($tokens) {
                $id = Auth::guard('auth')->id();
                return response()->json([
                    'message' => 'Authenticated',
                    'data' => [
                        'access_token' => $tokens['access_token'],
                        'access_token_expires_in' => $tokens['access_token_expires_in'],
                        'refresh_token' => $tokens['refresh_token'],
                        'user' => User::query()
                            ->where('id', $id)
                            ->first()->getFullInfo()
                    ],
                ]);
            } else {
                return response()->json([
                    'message' => 'Email ou senha inválidos',
                ], 401);
            }

        }

        return response()->json(['message' => 'Unauthorized'], 401);
    }

    public function refresh(): JsonResponse
    {
        $tokens = Auth::guard('auth')->attempt();
        if ($tokens) {
            return response()->json([
                'message' => 'Token refreshed',
                'data' => $tokens
            ]);
        }
        return response()->json(['message' => 'Unauthorized'], 401);
    }

    public function logout(): JsonResponse
    {
        if (Auth::guard('auth')->check()) {
            Auth::guard('auth')->logout();
            return response()->json(['message' => 'Logged out']);
        }
        return response()->json([
            'message' => 'Unauthorized',
        ], 401);
    }

    public function register(UserRequest $request): JsonResponse
    {

        if (Auth::guard('auth')->check())
            return response()->json([
                'message' => 'Usuário já autenticado',
            ])->setStatusCode(400);

        $user = new User();
        $data = $request->only(['email', 'name', 'password', 'confirm_password']);

        $user->fill($data)->save();

        $jwt = $user->generateJwt();

        $user = User::query()
            ->where('id', $user->id)
            ->first();

        return response()->json([
            'message' => 'Usuário cadastrado com sucesso',
            'data' => [
                'access_token' => $jwt['access_token'],
                'access_token_expires_in' => $jwt['access_token_expires_in'],
                'refresh_token' => $jwt['refresh_token'],
                'user' => $user->getFullInfo()
            ],
        ]);
    }
}
