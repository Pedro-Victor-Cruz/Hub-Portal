<?php

namespace App\Http\Controllers;

use App\Http\Requests\User\UserCreateOrUpdateRequest;
use App\Http\Requests\User\UserRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{

    public function profile(): JsonResponse
    {
        return response()->json([
            'message' => 'Usuário encontrado com sucesso',
            'data' => Auth::guard('auth')->user()
                ->getFullInfo()
        ]);
    }

    public function profileUpdate(UserRequest $request): JsonResponse
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

    public function index(): JsonResponse
    {
        $users = User::query()
            ->with(['company'])
            ->get();

        return response()->json([
            'message' => 'Usuários encontrados com sucesso',
            'data' => $users
        ]);
    }

    public function show(int $id): JsonResponse
    {
        /** @var User $user */
        $user = User::findOrFail($id);

        return response()->json([
            'message' => 'Usuário encontrado com sucesso',
            'data' => $user->getFullInfo()
        ]);
    }

    public function store(UserCreateOrUpdateRequest $request): JsonResponse
    {
        $data = $request->validated();

        try {
            $user = User::query()->create([
                'email' => $data['email'],
                'name' => $data['name'],
                'password' => $data['password'],
                'company_id' => $data['company_id'] ?? null,
            ]);

            return response()->json([
                'message' => 'Usuário criado com sucesso',
                'data' => $user->getFullInfo()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao criar usuário: ' . $e->getMessage(),
                'error' => $e->getTraceAsString(),
                'data' => null
            ], 500);
        }
    }

    public function update(UserCreateOrUpdateRequest $request, $id): JsonResponse
    {
        $user = User::query()->findOrFail($id);
        $data = $request->validated();

        if (!$user) {
            return response()->json([
                'message' => 'Usuário não encontrado',
                'data' => null
            ], 404);
        }

        try {
            $user->fill([
                'email' => $data['email'],
                'name' => $data['name'],
                'company_id' => $data['company_id'] ?? null,
            ]);

            if (isset($data['password']) && !empty($data['password'])) {
                $user->password = $data['password'];
            }

            $user->save();

            return response()->json([
                'message' => 'Usuário atualizado com sucesso',
                'data' => $user->getFullInfo()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao atualizar usuário: ' . $e->getMessage(),
                'error' => $e->getTraceAsString(),
                'data' => null
            ], 500);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        if (Auth::guard('auth')->user()->id === $id) {
            return response()->json([
                'message' => 'Você não pode excluir a si mesmo',
                'data' => null
            ], 403);
        }

        /** @var User $user */
        $user = User::query()->findOrFail($id);

        if ($user->isSuperAdmin()) {
            return response()->json([
                'message' => 'Você não pode excluir um super administrador',
                'data' => null
            ], 403);
        }

        $user->delete();

        return response()->json([
            'message' => 'Usuário deletado com sucesso',
            'data' => null
        ]);
    }


}
