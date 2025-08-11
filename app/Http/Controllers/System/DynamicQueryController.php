<?php

namespace App\Http\Controllers\System;

use App\Facades\DynamicQueryManager;
use App\Http\Controllers\Controller;
use App\Http\Requests\DynamicQuery\DuplicateDynamicQueryRequest;
use App\Http\Requests\DynamicQuery\StoreDynamicQueryRequest;
use App\Http\Requests\DynamicQuery\TestDynamicQueryRequest;
use App\Http\Requests\DynamicQuery\UpdateDynamicQueryRequest;
use App\Services\Core\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Controller para gerenciar e executar consultas dinâmicas
 */
class DynamicQueryController extends Controller
{
    
    /**
     * Lista todas as consultas disponíveis
     * GET /api/queries
     */
    public function index(Request $request): JsonResponse
    {
        $company = $request->get('company');
        $queries = DynamicQueryManager::getAvailableQueries($company);

        return ApiResponse::success($queries, "Lista de consultas")->toJson();
    }

    /**
     * Executa uma consulta dinâmica
     * POST /api/queries/{key}/execute
     */
    public function execute(Request $request, string $key): JsonResponse
    {
        $company = $request->get('company');
        $params = $request->all();

        // Remove parâmetros do sistema
        unset($params['company']);

        $response = DynamicQueryManager::executeQuery($key, $company, $params);

        return $response->toJson();
    }

    /**
     * Cria uma nova consulta dinâmica
     * POST /api/queries
     */
    public function store(StoreDynamicQueryRequest $request): JsonResponse
    {
        $data = $request->validated();
        $company = $request->get('company');

        if ($company) {
            $data['company_id'] = $company->id;
        }

        $response = DynamicQueryManager::createQuery($data);

        return $response->toJson();
    }

    /**
     * Atualiza uma consulta dinâmica existente
     * PUT /api/queries/{key}
     */
    public function update(UpdateDynamicQueryRequest $request, string $key): JsonResponse
    {
        $data = $request->validated();
        $company = $request->get('company');

        $response = DynamicQueryManager::updateQuery($key, $data, $company);

        return $response->toJson();
    }

    /**
     * Remove uma consulta dinâmica
     * DELETE /api/queries/{key}
     */
    public function destroy(Request $request, string $key): JsonResponse
    {
        $company = $request->get('company');
        $response = DynamicQueryManager::deleteQuery($key, $company);

        return $response->toJson();
    }

    /**
     * Testa uma consulta sem salvar
     * POST /api/queries/test
     */
    public function testQuery(TestDynamicQueryRequest $request): JsonResponse
    {
        $queryData = $request->only([
            'key', 'name', 'service_slug', 'service_params', 'query_config', 'fields_metadata'
        ]);
        $testParams = $request->input('test_params', []);

        $response = DynamicQueryManager::testQuery($queryData, $testParams);

        return $response->toJson();
    }

    /**
     * Duplica uma consulta global para a empresa
     * POST /api/queries/{key}/duplicate
     */
    public function duplicate(DuplicateDynamicQueryRequest $request, string $key): JsonResponse
    {
        $company = $request->get('company');

        if (!$company) {
            return response()->json([
                'success' => false,
                'message' => 'Empresa é obrigatória para duplicar consultas'
            ], 400);
        }

        $overrides = $request->validated();
        $response = DynamicQueryManager::duplicateQueryForCompany($key, $company, $overrides);

        return $response->toJson();
    }

    /**
     * Valida se uma consulta pode ser executada
     * GET /api/queries/{key}/validate
     */
    public function validateQuery(Request $request, string $rules): JsonResponse
    {
        $company = $request->get('company');
        $response = DynamicQueryManager::validateQueryExecution($rules, $company);

        return $response->toJson();
    }

    /**
     * Obtém informações detalhadas de uma consulta
     * GET /api/queries/{key}
     */
    public function show(Request $request, string $key): JsonResponse
    {
        $company = $request->get('company');

        // Reutiliza a validação que já busca a consulta
        $response = DynamicQueryManager::validateQueryExecution($key, $company);

        if (!$response->isSuccess()) {
            return response()->json($response->toArray(), 404);
        }

        return ApiResponse::success($response->getData(), "Detalhes da consulta '$key'")->toJson();
    }

    /**
     * Lista chaves de consultas disponíveis
     * GET /api/queries/keys
     */
    public function keys(Request $request): JsonResponse
    {
        $company = $request->get('company');
        $queries = DynamicQueryManager::getAvailableQueries($company);

        $keys = collect($queries)->pluck('key')->unique()->values();

        return ApiResponse::success($keys, "Lista de chaves de consultas")->toJson();
    }
}