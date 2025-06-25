<?php

namespace App\Http\Controllers;

use App\Http\Requests\Permission\AssignPermissionsToUserRequest;
use App\Http\Requests\Permission\PermissionGroupRequest;
use App\Models\Permission;
use App\Models\PermissionGroup;
use App\Models\User;
use App\Traits\ApiResponder;
use App\Utils\PermissionStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class PermissionController extends Controller
{

    use ApiResponder;

    public function accessLevels(): JsonResponse
    {
        /** @var User $user **/
        $user = Auth::guard('auth')->user();
        if (!$user) return response()->json(['message' => 'Usuário não autenticado.'], 401);

        return response()->json([
            'message' => 'Lista de níveis de acesso',
            'data' => PermissionStatus::getManageableLevelsArray($user->accessLevel())
        ]);
    }

    public function permissions(): JsonResponse
    {
        $isPaginate = request()->header('X-Paginate', false);

        $query = Permission::query()
            ->accessibleByCurrentUser()
            ->orderBy('id', 'desc');

        if ($isPaginate) {
            $paginate = $query->paginate($this->getPerPage());
            return $this->respondWithPagination($paginate, 'Lista de permissões');
        }

        return response()->json([
            'message' => 'Lista de permissões',
            'data' => $query->get()
        ]);
    }

    public function groups(): JsonResponse
    {
        $isPaginate = request()->header('X-Paginate', false);

        $groupsQuery = PermissionGroup::query()
            ->with('company')
            ->manageableByCurrentUser()
            ->orderBy('id', 'desc');

        if (request()->has('idCompany')) {
            $idCompany = request()->input('idCompany');
            $groupsQuery->where('company_id', $idCompany)->orWhereNull('company_id');
        }

        if ($isPaginate) {
            $groups = $groupsQuery->paginate($this->getPerPage());

            return $this->respondWithPagination($groups, 'Lista de grupos');
        } else {
            $groups = $groupsQuery->get();

            return response()->json(['message' => 'Lista de grupos', 'data' => $groups]);
        }
    }

    public function findGroup($idGroup): JsonResponse
    {
        $group = PermissionGroup::query()
            ->with('permissions')
            ->manageableByCurrentUser()
            ->find($idGroup);

        if (!$group) {
            return response()->json(['message' => 'Grupo de permissões não encontrado.'], 404);
        }

        return response()->json(['message' => 'Grupo de permissões encontrado.', 'data' => $group]);
    }

    public function createGroup(PermissionGroupRequest $request): JsonResponse
    {
        $data = $request->validated();

        /** @var PermissionGroup $group */
        $group = PermissionGroup::create($data);

        $group->syncPermissions($data['permissions'] ?? []);

        return response()->json([
            'message' => 'Grupo de permissões criado com sucesso.',
            'data' => $group
        ], 201);
    }

    public function updateGroup(PermissionGroupRequest $group, $id): JsonResponse
    {
        $data = $group->validated();

        /** @var PermissionGroup $permissionGroup */
        $permissionGroup = PermissionGroup::manageableByCurrentUser()->find($id);

        if (!$permissionGroup) {
            return response()->json(['message' => 'Grupo de permissões não encontrado.'], 404);
        }

        $permissionGroup->update($data);
        $permissionGroup->syncPermissions($data['permissions'] ?? []);

        return response()->json([
            'message' => 'Grupo de permissões atualizado com sucesso.',
            'data' => $permissionGroup
        ]);
    }

    public function deleteGroup($id): JsonResponse
    {
        /** @var PermissionGroup $permissionGroup */
        $permissionGroup = PermissionGroup::manageableByCurrentUser()->find($id);

        if (!$permissionGroup) {
            return response()->json(['message' => 'Grupo de permissões não encontrado.'], 404);
        }

        if ($permissionGroup->is_system) {
            return response()->json(['message' => 'Não é possível excluir um grupo de permissões do sistema.'], 403);
        }

        $permissionGroup->delete();

        return response()->json(['message' => 'Grupo de permissões excluído com sucesso.']);
    }

    public function assignPermissionsToUser(AssignPermissionsToUserRequest $request, $userId): JsonResponse
    {

        /** @var User $user */
        $user = User::find($userId);

        if (!$user) {
            return response()->json(['message' => 'Usuário não encontrado.'], 404);
        }

        $data = $request->validated();
        $permissions = $data['permissions'];

        // Verifica se o usuário tem permissão para atribuir essas permissões
        if (!$user->canBeAssignedPermissions($permissions)) {
            return response()->json(['message' => 'Você não tem permissão para atribuir essas permissões.'], 403);
        }

        // Atribui as permissões ao usuário
        $user->syncDirectPermissions($permissions);

        return response()->json([
            'message' => 'Permissões atribuídas com sucesso.',
        ]);
    }
}
