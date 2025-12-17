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
class DashboardFilterController extends Controller
{
    private FilterService $filterService;

    public function __construct(FilterService $filterService)
    {
        $this->filterService = $filterService;
    }

    /**
     * Lista filtros de uma consulta dinâmica
     * GET /api/dasboards/{queryKey}/filters
     */
    public function index(Request $request, string $key): JsonResponse
    {
        $onlyActive = $request->boolean('only_active', true);

        $response = $this->filterService->listFiltersByDashboard($key, $onlyActive);
        return $response->toJson();
    }

    /**
     * Cria um novo filtro
     * POST /api/dashboards/{key}/filters/create
     */
    public function store(StoreFilterRequest $request, string $key): JsonResponse
    {
        $filterData = $request->validated();

        $response = $this->filterService->createFilterByDashboard($key, $filterData);
        return $response->toJson();
    }


    /**
     * Atualiza um filtro existente
     * PUT /api/dashboards/{key}/filters/{varName}/update
     */
    public function update(UpdateFilterRequest $request, string $key, string $varName): JsonResponse
    {
        $filterData = $request->validated();

        $response = $this->filterService->updateFilterByDashboard($key, $varName, $filterData);
        return $response->toJson();
    }

    /**
     * Remove um filtro
     * DELETE /api/dashboards/{key}/filters/{varName}/delete
     */
    public function destroy(Request $request, string $key, string $varName): JsonResponse
    {
        $response = $this->filterService->deleteFilterByDashboard($key, $varName);
        return $response->toJson();
    }


    /**
     * Reordena filtros
     * PUT /api/dashboards/{key}/filters/reorder
     */
    public function reorder(Request $request, string $key): JsonResponse
    {
        $order = $request->input('order', []);

        $response = $this->filterService->reorderFiltersByDashboard($key, $order);
        return $response->toJson();
    }

    /**
     * Obtém configuração completa dos filtros para interface
     * GET /api/dashboards/{key}/filters/config
     */
    public function config(Request $request, string $key): JsonResponse
    {
        $response = $this->filterService->getFiltersConfigForUIByDashboard($key);
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
     * GET /api/dashboards/{key}/filters/variable-suggestions
     */
    public function variableSuggestions(): JsonResponse
    {

        return ApiResponse::success([
            'suggestions' => [],
            'variables_in_config' => [],
            'existing_filters' => [],
            'total_suggestions' => 0
        ], 'Sugestões de variáveis obtidas')->toJson();
    }

}