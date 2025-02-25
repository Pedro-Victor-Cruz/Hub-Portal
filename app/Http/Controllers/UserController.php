<?php

namespace App\Http\Controllers;

use App\Http\Requests\User\UserRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{

    public function index(): JsonResponse
    {
        $user = User::query()
            ->where('id', Auth::guard('auth')->id())
            ->with(['portals', 'portals.departments'])
            ->first();
        return response()->json([
            'message' => 'Usuario autenticado',
            'data' => $user
        ]);
    }

    public function update(UserRequest $request): JsonResponse
    {
        $user = Auth::guard('auth')->user();
        $data = $request->only(['email', 'name', 'password']);
        $user->fill($data)->save();

        return response()->json([
            'message' => 'Usuário atualizado com sucesso',
            'data' => User::query()
                ->where('id', $user->id)
                ->first()
        ]);
    }

}
