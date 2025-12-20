<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Services\Dashboard\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Controller para gerenciar dashboards dinâmicos
 */
class DashboardController extends Controller
{
    private DashboardService $service;

    public function __construct(DashboardService $service)
    {
        $this->service = $service;
    }

    /**
     * Lista todos os dashboards
     * GET /api/dashboards
     */
    public function index(Request $request): JsonResponse
    {
        $activeOnly = $request->boolean('active_only', true);
        return $this->service->listDashboards($activeOnly)->toJson();
    }

    /**
     * Obtém a estrutura completa de um dashboard
     * GET /api/dashboards/{key}
     */
    public function show(Request $request, string $key): JsonResponse
    {
        return $this->service->getDashboard($key)->toJson();
    }

    /**
     * Cria um novo dashboard
     * POST /api/dashboards/create
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'key' => 'required|string|max:100|unique:dashboards,key',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'icon' => 'nullable|string|max:50',
            'config' => 'nullable|array',
            'visibility' => 'nullable|string|in:public,authenticated,restricted',
            'active' => 'nullable|boolean',
            'is_navigable' => 'nullable|boolean',
            'is_home' => 'nullable|boolean',
        ], [
            'key.unique' => 'A chave do dashboard já está em uso. Escolha outra chave.',
            'key.required' => 'A chave do dashboard é obrigatória.',
            'name.required' => 'O nome do dashboard é obrigatório.',
            'key.max' => 'A chave do dashboard não pode exceder 100 caracteres.',
            'name.max' => 'O nome do dashboard não pode exceder 255 caracteres.',
            'icon.max' => 'O ícone do dashboard não pode exceder 50 caracteres.',

        ]);

        return $this->service->createDashboard($validated)->toJson();
    }

    /**
     * Atualiza um dashboard existente
     * PUT /api/dashboards/{key}/update
     */
    public function update(Request $request, string $key): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'icon' => 'nullable|string|max:50',
            'config' => 'nullable|array',
            'active' => 'nullable|boolean',
            'visibility' => 'nullable|string|in:public,authenticated,restricted',
            'is_navigable' => 'nullable|boolean',
            'is_home' => 'nullable|boolean',
        ], [
            'name.max' => 'O nome do dashboard não pode exceder 255 caracteres.',
            'icon.max' => 'O ícone do dashboard não pode exceder 50 caracteres.',
        ]);

        return $this->service->updateDashboard($key, $validated)->toJson();
    }

    /**
     * Remove um dashboard
     * DELETE /api/dashboards/{key}/delete
     */
    public function destroy(string $key): JsonResponse
    {
        return $this->service->deleteDashboard($key)->toJson();
    }

    /**
     * Duplica um dashboard
     * POST /api/dashboards/{key}/duplicate
     */
    public function duplicate(Request $request, string $key): JsonResponse
    {
        $validated = $request->validate([
            'new_key' => 'required|string|max:100|unique:dashboards,key',
            'new_name' => 'required|string|max:255',
        ]);

        return $this->service->duplicateDashboard(
            $key,
            $validated['new_key'],
            $validated['new_name']
        )->toJson();
    }

    /**
     * Buscar dashboards que são navegáveis
     * GET /api/dashboards/navigable/list
     */
    public function getNavigableDashboards(): JsonResponse
    {
        return $this->service->getNavigableDashboards()->toJson();
    }

    /**
     * Buscar dashboard que será carregado na home
     * GET /api/dashboards/home/list
     */
    public function getHomeDashboard(): JsonResponse
    {
        return $this->service->getHomeDashboard()->toJson();
    }

    /**
     * Cria uma nova seção em um dashboard
     * POST /api/dashboards/{key}/sections/create
     */
    public function createSection(Request $request, string $key): JsonResponse
    {
        $validated = $request->validate([
            'parent_section_id' => 'nullable|integer|exists:dashboard_sections,id',
            'key' => 'required|string|max:100',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'order' => 'nullable|integer',
            'active' => 'nullable|boolean',
        ]);

        return $this->service->createSection($key, $validated)->toJson();
    }

    /**
     * Atualiza uma seção
     * PUT /api/dashboards/sections/{sectionId}/update
     */
    public function updateSection(Request $request, int $sectionId): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'order' => 'nullable|integer',
            'active' => 'nullable|boolean',
        ]);

        return $this->service->updateSection($sectionId, $validated)->toJson();
    }

    /**
     * Remove uma seção
     * DELETE /api/dashboards/sections/{sectionId}/delete
     */
    public function deleteSection(int $sectionId): JsonResponse
    {
        return $this->service->deleteSection($sectionId)->toJson();
    }

    /**
     * Obtém dados de todos os widgets de uma seção
     * GET /api/dashboards/sections/{sectionId}/data
     */
    public function getSectionData(Request $request, int $sectionId): JsonResponse
    {
        $filterParams = $request->input('params', []);
        return $this->service->getSectionData($sectionId, $filterParams)->toJson();
    }

    /**
     * Listar todos os widgets de uma seção
     * GET /api/dashboards/sections/{sectionId}/widgets
     */
    public function listSectionWidgets(int $sectionId): JsonResponse
    {
        return $this->service->listSectionWidgets($sectionId)->toJson();
    }

    /**
     * Cria um novo widget em uma seção
     * POST /api/dashboards/sections/{sectionId}/widgets/create
     */
    public function createWidget(Request $request, int $sectionId): JsonResponse
    {
        $validated = $request->validate([
            'dynamic_query_id' => 'nullable|integer|exists:dynamic_queries,id',
            'key' => 'required|string|max:100',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'widget_type' => 'required|string|max:50',
            'order' => 'nullable|integer',
            'active' => 'nullable|boolean',
            'config' => 'nullable|array',
        ]);

        return $this->service->createWidget($sectionId, $validated)->toJson();
    }

    /**
     * Atualiza um widget
     * PUT /api/dashboards/widgets/{widgetId}/update
     */
    public function updateWidget(Request $request, int $widgetId): JsonResponse
    {
        $validated = $request->validate([
            'dynamic_query_id' => 'nullable|integer|exists:dynamic_queries,id',
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'widget_type' => 'sometimes|string|max:50',
            'order' => 'nullable|integer',
            'active' => 'nullable|boolean',
            'config' => 'nullable|array',
        ]);

        return $this->service->updateWidget($widgetId, $validated)->toJson();
    }

    /**
     * Remove um widget
     * DELETE /api/dashboards/widgets/{widgetId}/delete
     */
    public function deleteWidget(int $widgetId): JsonResponse
    {
        return $this->service->deleteWidget($widgetId)->toJson();
    }

    /**
     * Obtém dados de um widget específico
     * GET /api/dashboards/widgets/{widgetId}/data
     */
    public function getWidgetData(Request $request, int $widgetId): JsonResponse
    {
        $filterParams = $request->input('params', []);
        return $this->service->getWidgetData($widgetId, $filterParams)->toJson();
    }

    /**
     * Obtém os parâmetros de configuração de um widget
     * GET /api/dashboards/widgets/{widgetType}/parameters/
     */
    public function getParametersWidget(string $widgetType): JsonResponse
    {
        return $this->service->getWidgetParameters($widgetType)->toJson();
    }
}