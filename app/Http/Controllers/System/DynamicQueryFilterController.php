<?php

namespace App\Http\Controllers\System;

use App\Enums\FilterType;
use App\Http\Controllers\Controller;
use App\Http\Requests\DynamicQuery\StoreDynamicQueryFilterRequest;
use App\Http\Requests\DynamicQuery\UpdateDynamicQueryFilterRequest;
use App\Services\Core\ApiResponse;
use App\Services\Query\DynamicQueryFilterService;
use App\Services\Query\DynamicQueryManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use ValueError;

/**
 * Controller para gerenciar filtros de consultas dinâmicas
 */
class DynamicQueryFilterController extends Controller
{
    private DynamicQueryFilterService $filterService;

    public function __construct(DynamicQueryFilterService $filterService)
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

        $response = $this->filterService->listFilters($queryKey, $onlyActive);
        return $response->toJson();
    }

    /**
     * Cria um novo filtro
     * POST /api/queries/{queryKey}/filters/create
     */
    public function store(StoreDynamicQueryFilterRequest $request, string $queryKey): JsonResponse
    {
        $filterData = $request->validated();

        $response = $this->filterService->createFilter($queryKey, $filterData);
        return $response->toJson();
    }


    /**
     * Atualiza um filtro existente
     * PUT /api/queries/{queryKey}/filters/{varName}/update
     */
    public function update(UpdateDynamicQueryFilterRequest $request, string $queryKey, string $varName): JsonResponse
    {
        $filterData = $request->validated();

        $response = $this->filterService->updateFilter($queryKey, $varName, $filterData);
        return $response->toJson();
    }

    /**
     * Remove um filtro
     * DELETE /api/queries/{queryKey}/filters/{varName}/delete
     */
    public function destroy(Request $request, string $queryKey, string $varName): JsonResponse
    {
        $response = $this->filterService->deleteFilter($queryKey, $varName);
        return $response->toJson();
    }


    /**
     * Reordena filtros
     * PUT /api/queries/{queryKey}/filters/reorder
     */
    public function reorder(Request $request, string $queryKey): JsonResponse
    {
        $order = $request->input('order', []);

        $response = $this->filterService->reorderFilters($queryKey, $order);
        return $response->toJson();
    }

    /**
     * Obtém configuração completa dos filtros para interface
     * GET /api/queries/{queryKey}/filters/config
     */
    public function config(Request $request, string $queryKey): JsonResponse
    {
        $company = $request->get('company');

        $response = $this->filterService->getFiltersConfigForUI($queryKey, $company);
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
     * Obtém template para criação de filtro baseado no tipo
     * GET /api/queries/filters/template/{type}
     */
    public function filterTemplate(string $type): JsonResponse
    {
        try {
            $filterType = FilterType::from($type);

            $template = [
                'name' => '',
                'description' => null,
                'var_name' => '',
                'type' => $filterType->value,
                'default_value' => $filterType->getDefaultValue(),
                'required' => false,
                'order' => 0,
                'validation_rules' => [],
                'visible' => true,
                'active' => true,
                'options' => $filterType->requiresOptions() ? [] : null,
                'html_input_type' => $filterType->getHtmlInputType(),
                'is_multiple' => $filterType->isMultiple(),
                'requires_options' => $filterType->requiresOptions()
            ];

            return ApiResponse::success($template, "Template para filtro do tipo '{$filterType->getDescription()}'")->toJson();

        } catch (ValueError $e) {
            return ApiResponse::error("Tipo de filtro '{$type}' inválido")->toJson();
        }
    }

    /**
     * Obtém sugestões de variáveis baseadas na configuração da consulta
     * GET /api/queries/{queryKey}/filters/variable-suggestions
     */
    public function variableSuggestions(Request $request, string $queryKey): JsonResponse
    {
        $company = $request->get('company');

        // Busca a consulta
        $queryResponse = app(DynamicQueryManager::class)->getQueryWithFilters($queryKey, $company);

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