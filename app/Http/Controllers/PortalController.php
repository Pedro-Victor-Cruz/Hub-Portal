<?php

namespace App\Http\Controllers;

use App\Http\Requests\Portal\PortalRequest;
use App\Models\Department;
use App\Models\Portal;
use App\Models\PortalUsers;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class PortalController extends Controller
{

    public function index(): JsonResponse
    {
        $user = Auth::guard('auth')->user();

        return response()->json([
            'message' => 'Lista de portais',
            'data' => $user->portals()->with('departments')->get()
        ]);
    }

    public function create(PortalRequest $request): JsonResponse
    {
        // Obtém o ID do usuário autenticado
        $user_id = Auth::guard('auth')->id();

        // Cria a empresa
        $data = $request->only(['name', 'slug', 'phone']);
        $data['user_id'] = $user_id;
        $portal = Portal::create($data);

        // Vincula o usuário à empresa como proprietário
        PortalUsers::create([
            'user_id' => $user_id,
            'portal_id' => $portal->id,
        ]);

        Department::create([
            'portal_id' => $portal->id,
            'name' => $data['name'],
            'slug' => $data['slug'],
            'is_default' => true
        ]);


        return response()->json([
            'message' => 'Empresa criada com sucesso.',
            'data' => $portal->load('departments')
        ]);
    }

    public function show($id): JsonResponse
    {
        return response()->json([
            'message' => 'Portal com id ' . $id,
            'data' => Portal::with('departments')->find($id)
        ]);
    }

    public function update(PortalRequest $request, $id): JsonResponse
    {
        $data = $request->only(['name', 'slug', 'phone']);
        $portal = Portal::find($id);
        $portal->fill($data)->save();
        return response()->json([
            'message' => 'Portal atualizada',
            'data' => $portal
        ]);
    }

    public function delete($id): JsonResponse
    {
        $portal = Portal::find($id);

        if (!$portal)
            return response()->json([
                'message' => 'Portal não encontrada'
            ], 404);

        if ($portal->user_id != Auth::guard('auth')->id())
            return response()->json([
                'message' => 'Você não tem permissão para excluir esse portal'
            ], 403);

        $portal->delete();
        return response()->json([
            'message' => 'Portal deletado',
            'data' => $portal
        ]);
    }
}
