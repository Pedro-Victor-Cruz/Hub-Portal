<?php

namespace App\Http\Controllers\System;

use App\Enums\FilterType;
use App\Http\Controllers\Controller;
use App\Http\Requests\DynamicQuery\StoreFilterRequest;
use App\Http\Requests\DynamicQuery\UpdateFilterRequest;
use App\Services\Core\ApiResponse;
use App\Services\Query\FilterService;
use App\Services\Query\DynamicQueryManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use ValueError;

/**
 * Controller para gerenciar filtros de consultas dinâmicas
 */
class DynamicQueryFilterController extends Controller
{
    private FilterService $filterService;

    public function __construct(FilterService $filterService)
    {
        $this->filterService = $filterService;
    }

    /**
     * Lista filtros de uma consulta dinâmica
     * GET /api/queries/{queryKey}/filters
     */
    public function index(Request $request, string $queryKey): JsonResponse
    {
        $onlyActive = $request->boolean('only_active', true);

        $response = $this->filterService->listFiltersByQuery($queryKey, $onlyActive);
        return $response->toJson();
    }

    /**
     * Cria um novo filtro
     * POST /api/queries/{queryKey}/filters/create
     */
    public function store(StoreFilterRequest $request, string $queryKey): JsonResponse
    {
        $filterData = $request->validated();

        $response = $this->filterService->createFilterByQuery($queryKey, $filterData);
        return $response->toJson();
    }


    /**
     * Atualiza um filtro existente
     * PUT /api/queries/{queryKey}/filters/{varName}/update
     */
    public function update(UpdateFilterRequest $request, string $queryKey, string $varName): JsonResponse
    {
        $filterData = $request->validated();

        $response = $this->filterService->updateFilterByQuery($queryKey, $varName, $filterData);
        return $response->toJson();
    }

    /**
     * Remove um filtro
     * DELETE /api/queries/{queryKey}/filters/{varName}/delete
     */
    public function destroy(Request $request, string $queryKey, string $varName): JsonResponse
    {
        $response = $this->filterService->deleteFilterByQuery($queryKey, $varName);
        return $response->toJson();
    }


    /**
     * Reordena filtros
     * PUT /api/queries/{queryKey}/filters/reorder
     */
    public function reorder(Request $request, string $queryKey): JsonResponse
    {
        $order = $request->input('order', []);

        $response = $this->filterService->reorderFiltersByQuery($queryKey, $order);
        return $response->toJson();
    }

    /**
     * Obtém configuração completa dos filtros para interface
     * GET /api/queries/{queryKey}/filters/config
     */
    public function config(Request $request, string $queryKey): JsonResponse
    {
        $response = $this->filterService->getFiltersConfigForUIByQuery($queryKey);
        return $response->toJson();
    }

    /**
     * Obtém tipos de filtros disponíveis
     * GET /api/queries/filters/types
     */
    public function filterTypes(): JsonResponse
    {
        $types = FilterType::getOptions();

        return ApiResponse::success($types, 'Tipos de filtros disponíveis')->toJson();
    }


    /**
     * Obtém sugestões de variáveis baseadas na configuração da consulta
     * GET /api/queries/{queryKey}/filters/variable-suggestions
     */
    public function variableSuggestions(string $queryKey): JsonResponse
    {

        // Busca a consulta
        $queryResponse = app(DynamicQueryManager::class)->getQueryWithFilters($queryKey);

        if (!$queryResponse->isSuccess()) {
            return $queryResponse->toJson();
        }

        $queryData = $queryResponse->getData();
        $variablesInConfig = $queryData['variables_in_config'] ?? [];
        $existingFilters = collect($queryData['filters'])->pluck('var_name')->toArray();

        // Sugere variáveis que estão na config mas não têm filtro ainda
        $suggestions = array_diff($variablesInConfig, $existingFilters);

        return ApiResponse::success([
            'suggestions' => array_values($suggestions),
            'variables_in_config' => $variablesInConfig,
            'existing_filters' => $existingFilters,
            'total_suggestions' => count($suggestions)
        ], 'Sugestões de variáveis obtidas')->toJson();
    }
}