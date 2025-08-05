<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Http\Requests\System\ParameterRequest;
use App\Http\Requests\System\ParameterValueCompanyRequest;
use App\Http\Resources\ParameterResource;
use App\Models\Company;
use App\Models\Parameter;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class ParameterController extends Controller
{

    public function index(): JsonResponse
    {
        /** @var User $auth */
        $auth = Auth::guard('auth')->user();
        $companyId = request()->query('companyId');

        $query = Parameter::accessibleByCurrentUser();

        if ($companyId) {
            if ($auth->company_id != $companyId && !$auth->hasPermissionTo('company.view_other')) {
                return response()->json([
                    'message' => 'Você não tem permissão para acessar parâmetros de outras empresas.'
                ], 403);
            }

            $company = \App\Models\Company::find($companyId);
            abort_if(!$company, 404, 'Empresa não encontrada.');
        }

        $parameters = $query->orderByDesc('id')->get();

        return response()->json([
            'message' => 'Lista de parâmetros',
            'data' => ParameterResource::collection($parameters)
        ]);
    }



    public function show($id): JsonResponse
    {
        $parameter = Parameter::accessibleByCurrentUser()->findOrFail($id);
        return response()->json([
            'message' => 'Detalhes do parâmetro',
            'data' => $parameter
        ]);
    }

    public function store(ParameterRequest $request): JsonResponse
    {
        $data = $request->validated();
        return response()->json([
            'message' => 'Parâmetro criado com sucesso',
            'data' => Parameter::create($data)
        ], 201);
    }

    public function update(ParameterRequest $request, $id): JsonResponse
    {
        $parameter = Parameter::accessibleByCurrentUser()->findOrFail($id);
        $data = $request->validated();
        $parameter->update($data);
        return response()->json([
            'message' => 'Parâmetro atualizado com sucesso',
            'data' => $parameter
        ]);
    }

    public function destroy($id): JsonResponse
    {
        $parameter = Parameter::accessibleByCurrentUser()->findOrFail($id);
        $parameter->delete();
        return response()->json([
            'message' => 'Parâmetro excluído com sucesso'
        ]);
    }

    public function updateValueCompany(ParameterValueCompanyRequest $request, $id): JsonResponse
    {
        /** @var User $user */
        $auth = Auth::guard('auth')->user();
        $parameter = Parameter::accessibleByCurrentUser()->findOrFail($id);
        $data = $request->validated();

        // Assuming the request contains 'company_id' and 'value'
        $companyId = $data['company_id'];
        $value = $data['value'] ?? null;

        // Verifica se o usuário tem permissão para editar parâmetros de outras empresas
        if ($companyId && $companyId != $auth->company_id && !$auth->hasPermissionTo('company.edit_other')) {
            return response()->json([
                'message' => 'Você não tem permissão para atualizar parâmetros de outras empresas.'
            ], 403);
        }

        if (!is_null($value)) {
            $parameter->companies()->syncWithoutDetaching([
                $companyId => ['value' => $value]
            ]);

            return response()->json([
                'message' => 'Valor do parâmetro atualizado para a empresa',
                'data' => new ParameterResource($parameter)
            ]);
        } else {
            $parameter->companies()->detach($companyId);

            return response()->json([
                'message' => 'Valor do parâmetro removido para a empresa',
                'data' => new ParameterResource($parameter)
            ]);
        }

    }

    public function listCategories(): JsonResponse
    {
        $categories = Parameter::accessibleByCurrentUser()
            ->select('category')
            ->distinct()
            ->pluck('category')
            ->filter()
            ->values();

        return response()->json([
            'message' => 'Lista de categorias de parâmetros',
            'data' => $categories
        ]);
    }
}
