<?php

namespace App\Http\Controllers;

use App\Http\Requests\Department\DepartmentRequest;
use App\Http\Requests\Portal\PortalRequest;
use App\Models\Department;
use App\Models\Portal;
use App\Models\PortalUsers;
use http\Env\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class DepartmentController extends Controller
{

    public function index($slug_portal): JsonResponse
    {
        $user = Auth::guard('auth')->user();
        $portal = $user->portals()->where('slug', $slug_portal)->first();

        if (!$portal) {
            return response()->json(['message' => 'Portal não encontrado'], 404);
        }

        return response()->json([
            "message" => "Departamentos",
            "data" => $portal->departments()->get()
        ]);
    }

    public function show($slug_portal, $id): JsonResponse
    {
        $user = Auth::guard('auth')->user();
        $portal = $user->portals()->where('slug', $slug_portal)->first();

        if (!$portal) {
            return response()->json(['message' => 'Portal não encontrado'], 404);
        }

        $department = $portal->departments()->where('id', $id)->first();

        if (!$department) {
            return response()->json(['message' => 'Departamento não encontrado'], 404);
        }

        return response()->json([
            "message" => "Departamento",
            "data" => $department
        ]);
    }

    public function create($slug_portal, DepartmentRequest $request): JsonResponse
    {
        $user = Auth::guard('auth')->user();
        $portal = $user->portals()->where('slug', $slug_portal)->first();

        if (!$portal) {
            return response()->json(['message' => 'Portal não encontrado'], 404);
        }

        $data = $request->validated();
        $data['portal_id'] = $portal->id;

        $department = Department::create($data);

        return response()->json([
            "message" => "Departamento criado com sucesso",
            "data" => $department
        ]);
    }

    public function update($slug_portal, DepartmentRequest $request, $id): JsonResponse
    {
        $user = Auth::guard('auth')->user();
        $portal = $user->portals()->where('slug', $slug_portal)->first();

        if (!$portal) {
            return response()->json(['message' => 'Portal não encontrado'], 404);
        }

        $department = $portal->departments()->where('id', $id)->first();

        if (!$department) {
            return response()->json(['message' => 'Departamento não encontrado'], 404);
        }

        $data = $request->validated();
        $department->fill($data)->save();

        return response()->json([
            "message" => "Departamento atualizado com sucesso",
            "data" => $department
        ]);
    }

    public function delete($slug_portal, $id): JsonResponse
    {
        $user = Auth::guard('auth')->user();
        $portal = $user->portals()->where('slug', $slug_portal)->first();

        if (!$portal) {
            return response()->json(['message' => 'Portal não encontrado'], 404);
        }

        $department = $portal->departments()->where('id', $id)->first();

        if (!$department) {
            return response()->json(['message' => 'Departamento não encontrado'], 404);
        }

        if ($department->is_default) {
            return response()->json(['message' => 'Departamento principal não pode ser deletado'], 400);
        }

        $department->delete();

        return response()->json([
            "message" => "Departamento deletado com sucesso",
            "data" => $department
        ]);
    }
}
