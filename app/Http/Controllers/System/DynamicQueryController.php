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
        $queries = DynamicQueryManager::getAvailableQueries();

        return ApiResponse::success($queries, "Lista de consultas")->toJson();
    }

    /**
     * Executa uma consulta dinâmica
     * POST /api/queries/{key}/execute
     */
    public function execute(Request $request, string $key): JsonResponse
    {
        $params = $request->input('params', []);
        $response = DynamicQueryManager::executeQuery($key, $params);

        return $response->toJson();
    }

    /**
     * Cria uma nova consulta dinâmica
     * POST /api/queries
     */
    public function store(StoreDynamicQueryRequest $request): JsonResponse
    {
        return DynamicQueryManager::createQuery($request->validated())->toJson();
    }

    /**
     * Atualiza uma consulta dinâmica existente
     * PUT /api/queries/{key}
     */
    public function update(UpdateDynamicQueryRequest $request, string $key): JsonResponse
    {
        $data = $request->validated();

        $response = DynamicQueryManager::updateQuery($key, $data);

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
     * Valida se uma consulta pode ser executada
     * GET /api/queries/{key}/validate
     */
    public function validateQuery(string $rules): JsonResponse
    {
        $response = DynamicQueryManager::validateQueryExecution($rules);

        return $response->toJson();
    }

    /**
     * Obtém informações detalhadas de uma consulta
     * GET /api/queries/{key}
     */
    public function show(string $key): JsonResponse
    {
        // Reutiliza a validação que já busca a consulta
        $response = DynamicQueryManager::validateQueryExecution($key);

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