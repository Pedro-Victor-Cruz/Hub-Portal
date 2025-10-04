<?php

namespace App\Http\Controllers\System;

use App\Facades\ActivityLog;
use App\Http\Controllers\Controller;
use App\Http\Requests\System\ParameterRequest;
use App\Http\Requests\System\ParameterValueCompanyRequest;
use App\Http\Resources\ParameterResource;
use App\Models\Company;
use App\Models\Parameter;
use App\Models\SystemLog;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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
                // Log de tentativa de acesso não autorizado
                ActivityLog::log(
                    action: SystemLog::ACTION_UNAUTHORIZED_ACCESS,
                    description: "Tentativa de acessar parâmetros de outra empresa sem permissão (Company ID: {$companyId})",
                    level: SystemLog::LEVEL_WARNING,
                    module: 'parameter',
                    data: [
                        'metadata' => [
                            'attempted_company_id' => $companyId,
                            'user_company_id' => $auth->company_id,
                            'reason' => 'Sem permissão company.view_other'
                        ]
                    ]
                );

                return response()->json([
                    'message' => 'Você não tem permissão para acessar parâmetros de outras empresas.'
                ], 403);
            }

            $company = Company::find($companyId);
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

        // Log de visualização de parâmetro específico
        ActivityLog::logViewed(
            model: $parameter,
            description: "Visualização do parâmetro: {$parameter->name}"
        );

        return response()->json([
            'message' => 'Detalhes do parâmetro',
            'data' => $parameter
        ]);
    }

    public function store(ParameterRequest $request): JsonResponse
    {
        $data = $request->validated();

        try {
            DB::beginTransaction();

            $parameter = Parameter::create($data);

            // Log de criação de parâmetro
            ActivityLog::logCreated(
                model: $parameter,
                description: "Novo parâmetro criado: {$parameter->name} (Categoria: {$parameter->category})"
            );

            DB::commit();

            return response()->json([
                'message' => 'Parâmetro criado com sucesso',
                'data' => $parameter
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            // Log de erro ao criar parâmetro
            ActivityLog::logError(
                description: "Erro ao criar parâmetro: {$e->getMessage()}",
                module: 'parameter',
                context: [
                    'data' => $data,
                    'error' => $e->getMessage()
                ]
            );

            return response()->json([
                'message' => 'Erro ao criar parâmetro',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(ParameterRequest $request, $id): JsonResponse
    {
        $parameter = Parameter::accessibleByCurrentUser()->findOrFail($id);

        // Salva dados antigos para log
        $oldData = $parameter->getOriginal();
        $data = $request->validated();

        try {
            DB::beginTransaction();

            $parameter->update($data);

            // Log de atualização de parâmetro
            ActivityLog::logUpdated(
                model: $parameter,
                oldValues: $oldData,
                description: "Parâmetro atualizado: {$parameter->name}"
            );

            DB::commit();

            return response()->json([
                'message' => 'Parâmetro atualizado com sucesso',
                'data' => $parameter
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            // Log de erro ao atualizar parâmetro
            ActivityLog::logError(
                description: "Erro ao atualizar parâmetro ID {$id}: {$e->getMessage()}",
                module: 'parameter',
                context: [
                    'parameter_id' => $id,
                    'error' => $e->getMessage()
                ]
            );

            return response()->json([
                'message' => 'Erro ao atualizar parâmetro',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id): JsonResponse
    {
        $parameter = Parameter::accessibleByCurrentUser()->findOrFail($id);

        try {
            DB::beginTransaction();

            // Log ANTES de deletar (para ter os dados)
            ActivityLog::logDeleted(
                model: $parameter,
                description: "Parâmetro deletado: {$parameter->name} (Categoria: {$parameter->category})"
            );

            $parameter->delete();

            DB::commit();

            return response()->json([
                'message' => 'Parâmetro excluído com sucesso'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            // Log de erro ao deletar parâmetro
            ActivityLog::logError(
                description: "Erro ao deletar parâmetro ID {$id}: {$e->getMessage()}",
                module: 'parameter',
                context: [
                    'parameter_id' => $id,
                    'error' => $e->getMessage()
                ]
            );

            return response()->json([
                'message' => 'Erro ao deletar parâmetro',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function updateValueCompany(ParameterValueCompanyRequest $request, $id): JsonResponse
    {
        /** @var User $auth */
        $auth = Auth::guard('auth')->user();
        $parameter = Parameter::accessibleByCurrentUser()->findOrFail($id);
        $data = $request->validated();

        $companyId = $data['company_id'];
        $value = $data['value'] ?? null;

        // Verifica se o usuário tem permissão para editar parâmetros de outras empresas
        if ($companyId && $companyId != $auth->company_id && !$auth->hasPermissionTo('company.edit_other')) {
            // Log de tentativa de edição não autorizada
            ActivityLog::log(
                action: SystemLog::ACTION_UNAUTHORIZED_EDIT_ATTEMPT,
                description: "Tentativa de atualizar valor de parâmetro para outra empresa sem permissão (Company ID: {$companyId}, Parameter: {$parameter->name})",
                level: SystemLog::LEVEL_WARNING,
                module: 'parameter',
                model: $parameter,
                data: [
                    'metadata' => [
                        'attempted_company_id' => $companyId,
                        'user_company_id' => $auth->company_id,
                        'parameter_id' => $id,
                        'reason' => 'Sem permissão company.edit_other'
                    ]
                ]
            );

            return response()->json([
                'message' => 'Você não tem permissão para atualizar parâmetros de outras empresas.'
            ], 403);
        }

        try {
            DB::beginTransaction();

            $company = Company::find($companyId);
            $companyName = $company ? $company->name : "ID {$companyId}";

            if (!is_null($value)) {
                // Verifica se já existe um valor anterior
                $previousValue = $parameter->companies()
                    ->where('company_id', $companyId)
                    ->first();

                $parameter->companies()->syncWithoutDetaching([
                    $companyId => ['value' => $value]
                ]);

                // Log de atualização/definição de valor
                $action = $previousValue ? 'updated' : 'created';
                ActivityLog::log(
                    action: "parameter_value_{$action}",
                    description: "Valor do parâmetro '{$parameter->name}' " .
                    ($previousValue ? "atualizado" : "definido") .
                    " para a empresa '{$companyName}': {$value}",
                    level: SystemLog::LEVEL_INFO,
                    module: 'parameter',
                    model: $parameter,
                    data: [
                        'metadata' => [
                            'company_id' => $companyId,
                            'company_name' => $companyName,
                            'parameter_name' => $parameter->name,
                            'old_value' => $previousValue ? $previousValue->pivot->value : null,
                            'new_value' => $value
                        ]
                    ]
                );

                DB::commit();

                return response()->json([
                    'message' => 'Valor do parâmetro atualizado para a empresa',
                    'data' => new ParameterResource($parameter)
                ]);
            } else {
                // Captura o valor antes de remover
                $previousValue = $parameter->companies()
                    ->where('company_id', $companyId)
                    ->first();

                $parameter->companies()->detach($companyId);

                // Log de remoção de valor
                ActivityLog::log(
                    action: 'parameter_value_removed',
                    description: "Valor do parâmetro '{$parameter->name}' removido para a empresa '{$companyName}'",
                    level: SystemLog::LEVEL_NOTICE,
                    module: 'parameter',
                    model: $parameter,
                    data: [
                        'metadata' => [
                            'company_id' => $companyId,
                            'company_name' => $companyName,
                            'parameter_name' => $parameter->name,
                            'removed_value' => $previousValue ? $previousValue->pivot->value : null
                        ]
                    ]
                );

                DB::commit();

                return response()->json([
                    'message' => 'Valor do parâmetro removido para a empresa',
                    'data' => new ParameterResource($parameter)
                ]);
            }
        } catch (\Exception $e) {
            DB::rollBack();

            // Log de erro ao atualizar valor da empresa
            ActivityLog::logError(
                description: "Erro ao atualizar valor do parâmetro para empresa: {$e->getMessage()}",
                module: 'parameter',
                context: [
                    'parameter_id' => $id,
                    'company_id' => $companyId,
                    'error' => $e->getMessage()
                ]
            );

            return response()->json([
                'message' => 'Erro ao atualizar valor do parâmetro',
                'error' => $e->getMessage()
            ], 500);
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