<?php

namespace App\Http\Controllers\User;

use App\Facades\ActivityLog;
use App\Http\Controllers\Controller;
use App\Http\Requests\User\UserCreateOrUpdateRequest;
use App\Http\Requests\User\UserRequest;
use App\Models\SystemLog;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{

    public function profile(): JsonResponse
    {
        $user = Auth::guard('auth')->user();

        // Log de visualização do próprio perfil (nível debug - menos crítico)
        ActivityLog::logViewed(
            model: $user,
            description: "Usuário visualizou o próprio perfil"
        );

        return response()->json([
            'message' => 'Usuário encontrado com sucesso',
            'data' => $user->getFullInfo()
        ]);
    }

    public function profileUpdate(UserRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = Auth::guard('auth')->user();
        $oldData = $user->getOriginal();
        $data = $request->only(['email', 'name', 'password']);

        $user->fill($data)->save();

        // Log de atualização do próprio perfil
        ActivityLog::logUpdated(
            model: $user,
            oldValues: $oldData,
            description: "Usuário atualizou o próprio perfil"
        );

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

        // Log de listagem (opcional - pode gerar muito log)
        // Descomente se quiser logar todas as listagens
        // ActivityLog::log(
        //     action: SystemLog::ACTION_VIEWED,
        //     description: "Listagem de usuários acessada",
        //     level: SystemLog::LEVEL_DEBUG,
        //     module: 'user'
        // );

        return response()->json([
            'message' => 'Usuários encontrados com sucesso',
            'data' => $users
        ]);
    }

    public function show(int $id): JsonResponse
    {
        /** @var User $user */
        $user = User::findOrFail($id);

        // Log de visualização de usuário específico
        ActivityLog::logViewed(
            model: $user,
            description: "Visualização detalhada do usuário: {$user->name}"
        );

        return response()->json([
            'message' => 'Usuário encontrado com sucesso',
            'data' => $user->getFullInfo()
        ]);
    }

    public function store(UserCreateOrUpdateRequest $request): JsonResponse
    {
        /** @var User $auth */
        $auth = Auth::guard('auth')->user();
        $data = $request->validated();

        if (!$auth->hasPermissionTo('company.create_other')) {
            $data['company_id'] = $auth->company_id;
        }

        try {
            DB::beginTransaction();

            $user = User::query()->create([
                'email' => $data['email'],
                'name' => $data['name'],
                'password' => $data['password'],
                'company_id' => $data['company_id'] ?? null,
            ]);

            // Log de criação de usuário
            ActivityLog::logCreated(
                model: $user,
                description: "Novo usuário criado: {$user->name} ({$user->email})"
            );

            DB::commit();

            return response()->json([
                'message' => 'Usuário criado com sucesso',
                'data' => $user->getFullInfo()
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            // Log de erro ao criar usuário
            ActivityLog::logError(
                description: "Erro ao criar usuário: {$e->getMessage()}",
                module: 'user',
                context: [
                    'email' => $data['email'] ?? null,
                    'name' => $data['name'] ?? null,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]
            );

            return response()->json([
                'message' => 'Erro ao criar usuário: ' . $e->getMessage(),
                'error' => $e->getTraceAsString(),
                'data' => null
            ], 500);
        }
    }

    public function update(UserCreateOrUpdateRequest $request, $id): JsonResponse
    {
        /** @var User $auth */
        $auth = Auth::guard('auth')->user();
        $user = User::query()->findOrFail($id);

        if (!$user) {
            return response()->json([
                'message' => 'Usuário não encontrado',
                'data' => null
            ], 404);
        }

        // Salva dados antigos para log
        $oldData = $user->getOriginal();
        $data = $request->validated();

        if (!$auth->hasPermissionTo('company.edit_other')) {
            $data['company_id'] = $user->company_id;
        }

        try {
            DB::beginTransaction();

            $user->fill([
                'email' => $data['email'],
                'name' => $data['name'],
                'company_id' => $data['company_id'] ?? null,
            ]);

            if (isset($data['password']) && !empty($data['password'])) {
                $user->password = $data['password'];
            }

            $user->save();

            // Log de atualização de usuário
            ActivityLog::logUpdated(
                model: $user,
                oldValues: $oldData,
                description: "Usuário atualizado: {$user->name}"
            );

            DB::commit();

            return response()->json([
                'message' => 'Usuário atualizado com sucesso',
                'data' => $user->getFullInfo()
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            // Log de erro ao atualizar usuário
            ActivityLog::logError(
                description: "Erro ao atualizar usuário ID {$id}: {$e->getMessage()}",
                module: 'user',
                context: [
                    'user_id' => $id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]
            );

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
            // Log de tentativa de auto-exclusão (segurança)
            ActivityLog::log(
                action: 'delete_attempt_blocked',
                description: "Tentativa bloqueada de auto-exclusão",
                level: SystemLog::LEVEL_WARNING,
                module: 'user'
            );

            return response()->json([
                'message' => 'Você não pode excluir a si mesmo',
                'data' => null
            ], 403);
        }

        /** @var User $user */
        $user = User::query()->findOrFail($id);

        if ($user->isSuperAdmin()) {
            // Log de tentativa de exclusão de super admin (segurança)
            ActivityLog::log(
                action: 'delete_superadmin_blocked',
                description: "Tentativa bloqueada de exclusão do super admin: {$user->name}",
                level: SystemLog::LEVEL_WARNING,
                module: 'user',
                model: $user
            );

            return response()->json([
                'message' => 'Você não pode excluir um super administrador',
                'data' => null
            ], 403);
        }

        try {
            DB::beginTransaction();

            // Log ANTES de deletar (para ter os dados)
            ActivityLog::logDeleted(
                model: $user,
                description: "Usuário deletado: {$user->name} ({$user->email})"
            );

            $user->delete();

            DB::commit();

            return response()->json([
                'message' => 'Usuário deletado com sucesso',
                'data' => null
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            // Log de erro ao deletar usuário
            ActivityLog::logError(
                description: "Erro ao deletar usuário ID {$id}: {$e->getMessage()}",
                module: 'user',
                context: [
                    'user_id' => $id,
                    'error' => $e->getMessage()
                ]
            );

            return response()->json([
                'message' => 'Erro ao deletar usuário: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }
}