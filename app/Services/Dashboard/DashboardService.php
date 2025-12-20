<?php

namespace App\Services\Dashboard;

use App\Facades\DynamicQueryManager;
use App\Models\Dashboard;
use App\Models\DashboardSection;
use App\Models\DashboardWidget;
use App\Services\Core\ApiResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Serviço principal para gerenciamento de Dashboards
 */
class DashboardService
{

    public function listDashboards(bool $activeOnly = true): ApiResponse
    {
        try {
            $query = Dashboard::with(['rootSections', 'filters']);

            if ($activeOnly) {
                $query->active();
            }

            $dashboards = $query->get()->map(function ($dashboard) {
                $readiness = $dashboard->isReady();
                return [
                    'key'         => $dashboard->key,
                    'name'        => $dashboard->name,
                    'description' => $dashboard->description,
                    'icon'        => $dashboard->icon,
                    'active'      => $dashboard->active,
                    'is_home'     => $dashboard->is_home,
                    'is_navigable'=> $dashboard->is_navigable,
                    'ready'       => $readiness['ready'],
                ];
            });

            return ApiResponse::success($dashboards, 'Lista de dashboards');
        } catch (\Exception $e) {
            return ApiResponse::error('Erro ao listar dashboards', [$e->getMessage()]);
        }
    }

    public function getDashboard(string $key): ApiResponse
    {
        try {
            /** @var Dashboard $dashboard */
            $dashboard = Dashboard::where('key', $key)->active()->first();

            if (!$dashboard) {
                return ApiResponse::error("Dashboard '{$key}' não encontrado");
            }

            if (!$dashboard->userHasAccess()) {
                return ApiResponse::error("Acesso negado ao dashboard '{$dashboard->name}'");
            }

            $structure = $dashboard->getFullStructure();


            return ApiResponse::success($structure, "Dashboard '{$key}' carregado");
        } catch (\Exception $e) {
            return ApiResponse::error('Erro ao carregar dashboard', [$e->getMessage()]);
        }
    }

    public function createDashboard(array $data): ApiResponse
    {
        DB::beginTransaction();
        try {
            $dashboard = Dashboard::create([
                'key'          => $data['key'],
                'name'         => $data['name'],
                'description'  => $data['description'] ?? null,
                'icon'         => $data['icon'] ?? null,
                'config'       => $data['config'] ?? null,
                'visibility'   => $data['visibility'] ?? null,
                'active'       => $data['active'] ?? true,
                'is_navigable' => $data['is_navigable'] ?? false,
                'is_home'      => $data['is_home'] ?? false,
            ]);

            DB::commit();
            return ApiResponse::success($dashboard->fresh(), 'Dashboard criado com sucesso');
        } catch (\Exception $e) {
            DB::rollBack();
            return ApiResponse::error('Erro ao criar dashboard', [$e->getMessage()]);
        }
    }

    public function updateDashboard(string $key, array $data): ApiResponse
    {
        DB::beginTransaction();
        try {
            $dashboard = Dashboard::where('key', $key)->first();

            if (!$dashboard) {
                return ApiResponse::error("Dashboard '{$key}' não encontrado");
            }

            $dashboard->update(array_filter([
                'name'         => $data['name'] ?? null,
                'description'  => $data['description'] ?? null,
                'icon'         => $data['icon'] ?? null,
                'config'       => $data['config'] ?? null,
                'active'       => $data['active'] ?? null,
                'visibility'   => $data['visibility'] ?? null,
                'is_navigable' => $data['is_navigable'] ?? false,
                'is_home'      => $data['is_home'] ?? false,
            ], fn($value) => $value !== null));

            DB::commit();
            return ApiResponse::success($dashboard->fresh(), 'Dashboard atualizado com sucesso');
        } catch (\Exception $e) {
            DB::rollBack();
            return ApiResponse::error('Erro ao atualizar dashboard', [$e->getMessage()]);
        }
    }

    public function deleteDashboard(string $key): ApiResponse
    {
        DB::beginTransaction();
        try {
            $dashboard = Dashboard::where('key', $key)->first();

            if (!$dashboard) {
                return ApiResponse::error("Dashboard '{$key}' não encontrado");
            }

            $dashboard->delete();

            DB::commit();
            return ApiResponse::success(null, 'Dashboard removido com sucesso');
        } catch (\Exception $e) {
            DB::rollBack();
            return ApiResponse::error('Erro ao remover dashboard', [$e->getMessage()]);
        }
    }

    public function duplicateDashboard(string $sourceKey, string $newKey, string $newName): ApiResponse
    {
        DB::beginTransaction();
        try {
            $source = Dashboard::where('key', $sourceKey)->first();

            if (!$source) {
                return ApiResponse::error("Dashboard '{$sourceKey}' não encontrado");
            }

            if (Dashboard::where('key', $newKey)->exists()) {
                return ApiResponse::error("Já existe um dashboard com a chave '{$newKey}'");
            }

            $newDashboard = $source->duplicate($newKey, $newName);

            DB::commit();
            return ApiResponse::success($newDashboard->fresh(), 'Dashboard duplicado com sucesso');
        } catch (\Exception $e) {
            DB::rollBack();
            return ApiResponse::error('Erro ao duplicar dashboard', [$e->getMessage()]);
        }
    }


    public function createSection(string $dashboardKey, array $data): ApiResponse
    {
        DB::beginTransaction();
        try {
            $dashboard = Dashboard::where('key', $dashboardKey)->first();

            if (!$dashboard) {
                return ApiResponse::error("Dashboard '{$dashboardKey}' não encontrado");
            }

            // Calcula o nível baseado no pai
            $level = 1;
            if (!empty($data['parent_section_id'])) {
                $parent = DashboardSection::find($data['parent_section_id']);
                if (!$parent || $parent->dashboard_id !== $dashboard->id) {
                    return ApiResponse::error('Seção pai inválida');
                }
                $level = $parent->level + 1;
            }

            $section = DashboardSection::create([
                'dashboard_id'      => $dashboard->id,
                'parent_section_id' => $data['parent_section_id'] ?? null,
                'key'               => $data['key'],
                'title'             => $data['title'],
                'description'       => $data['description'] ?? null,
                'level'             => $level,
                'order'             => $data['order'] ?? 0,
                'active'            => $data['active'] ?? true,
            ]);

            DB::commit();
            return ApiResponse::success($section->fresh(), 'Seção criada com sucesso');
        } catch (\Exception $e) {
            DB::rollBack();
            return ApiResponse::error('Erro ao criar seção', [$e->getMessage()]);
        }
    }

    public function updateSection(int $sectionId, array $data): ApiResponse
    {
        DB::beginTransaction();
        try {
            $section = DashboardSection::find($sectionId);

            if (!$section) {
                return ApiResponse::error('Seção não encontrada');
            }

            $section->update(array_filter([
                'title'       => $data['title'] ?? null,
                'description' => $data['description'] ?? null,
                'order'       => $data['order'] ?? null,
                'active'      => $data['active'] ?? null,
            ], fn($value) => $value !== null));

            DB::commit();
            return ApiResponse::success($section->fresh(), 'Seção atualizada com sucesso');
        } catch (\Exception $e) {
            DB::rollBack();
            return ApiResponse::error('Erro ao atualizar seção', [$e->getMessage()]);
        }
    }

    public function deleteSection(int $sectionId): ApiResponse
    {
        DB::beginTransaction();
        try {
            $section = DashboardSection::find($sectionId);

            if (!$section) {
                return ApiResponse::error('Seção não encontrada');
            }

            // Verifica se tem filhos
            if ($section->hasChildren()) {
                return ApiResponse::error('Não é possível deletar seção com subseções');
            }

            $section->delete();

            DB::commit();
            return ApiResponse::success(null, 'Seção removida com sucesso');
        } catch (\Exception $e) {
            DB::rollBack();
            return ApiResponse::error('Erro ao remover seção', [$e->getMessage()]);
        }
    }

    /**
     * Carrega dados de uma seção com otimização de queries duplicadas
     *
     * @param int $sectionId
     * @param array $filterParams
     * @return ApiResponse
     */
    public function getSectionData(int $sectionId, array $filterParams = []): ApiResponse
    {
        try {
            /** @var DashboardSection $section */
            $section = DashboardSection::with([
                'widgets' => function ($query) {
                    $query->active()->with('dynamicQuery');
                }
            ])->find($sectionId);


            if (!$section) {
                return ApiResponse::error('Seção não encontrada');
            }

            /** @var Dashboard $dashboard */
            $dashboard = $section->dashboard;

            if (!$dashboard->userHasAccess()) {
                return ApiResponse::error('Acesso negado para buscar dados da seção');
            }

            $widgetsData = [];
            $errors = [];

            // Cache para armazenar resultados de queries já executadas
            $queryCache = [];

            foreach ($section->widgets as $widget) {

                // Verifica se o widget tem dynamic_query_id
                if (!$widget->dynamic_query_id || !$widget->dynamicQuery) {
                    $errors[$widget->key] = ['Widget não possui consulta configurada'];
                    continue;
                }

                $queryKey = $widget->dynamicQuery->key;

                // Cria uma chave única considerando a query e os filtros
                $cacheKey = $queryKey . '_' . md5(json_encode($filterParams));

                // Se a query já foi executada, reutiliza o resultado
                if (isset($queryCache[$cacheKey])) {
                    $cachedResult = $queryCache[$cacheKey];

                    if ($cachedResult['success']) {
                        $widgetsData[$widget->key] = [
                            'widget' => [
                                'id'    => $widget->id,
                                'key'   => $widget->key,
                                'title' => $widget->title,
                                'type'  => $widget->widget_type,
                            ],
                            'data'   => $cachedResult['data'],
                            'config' => [
                                'chart_config' => $widget->chart_config,
                            ],
                            'cached' => true, // Flag opcional para debug
                        ];
                    } else {
                        $errors[$widget->key] = $cachedResult['errors'];
                    }

                    continue;
                }

                // Executa a query pela primeira vez
                try {
                    $queryResponse = DynamicQueryManager::executeQuery($queryKey, $filterParams);

                    if ($queryResponse->isSuccess()) {
                        $data = $queryResponse->getData();

                        // Armazena no cache
                        $queryCache[$cacheKey] = [
                            'success' => true,
                            'data'    => $data,
                        ];

                        $widgetsData[$widget->key] = [
                            'widget' => [
                                'id'    => $widget->id,
                                'key'   => $widget->key,
                                'title' => $widget->title,
                                'type'  => $widget->widget_type,
                            ],
                            'data'   => $data,
                            'config' => [
                                'chart_config' => $widget->chart_config,
                            ],
                        ];
                    } else {
                        $queryErrors = $queryResponse->getErrors();

                        // Armazena o erro no cache
                        $queryCache[$cacheKey] = [
                            'success' => false,
                            'errors'  => $queryErrors,
                        ];

                        $errors[$widget->key] = $queryErrors;
                    }
                } catch (\Exception $e) {
                    $errorMessage = $e->getMessage();

                    // Armazena o erro no cache
                    $queryCache[$cacheKey] = [
                        'success' => false,
                        'errors'  => [$errorMessage],
                    ];

                    $errors[$widget->key] = [$errorMessage];
                    Log::error("Erro ao executar query para widget {$widget->key}: " . $errorMessage);
                }
            }

            return ApiResponse::success([
                'section' => [
                    'id'    => $section->id,
                    'key'   => $section->key,
                    'title' => $section->title,
                ],
                'widgets' => $widgetsData,
                'errors'  => $errors,
            ], 'Dados da seção carregados');
        } catch (\Exception $e) {
            Log::error("Erro ao carregar dados da seção {$sectionId}: " . $e->getMessage());
            return ApiResponse::error('Erro ao carregar dados da seção', [$e->getMessage()]);
        }
    }

    public function createWidget(int $sectionId, array $data): ApiResponse
    {
        DB::beginTransaction();
        try {
            $section = DashboardSection::find($sectionId);

            if (!$section) {
                return ApiResponse::error('Seção não encontrada');
            }

            $widget = DashboardWidget::create([
                'section_id'       => $sectionId,
                'dynamic_query_id' => $data['dynamic_query_id'] ?? null,
                'key'              => $data['key'],
                'title'            => $data['title'],
                'description'      => $data['description'] ?? null,
                'widget_type'      => $data['widget_type'],
                'order'            => $data['order'] ?? 0,
                'active'           => $data['active'] ?? true,
                'config'           => $data['config'] ?? null,
            ]);

            DB::commit();
            return ApiResponse::success($widget->fresh(), 'Widget criado com sucesso');
        } catch (\Exception $e) {
            DB::rollBack();
            return ApiResponse::error('Erro ao criar widget', [$e->getMessage()]);
        }
    }

    public function updateWidget(int $widgetId, array $data): ApiResponse
    {
        DB::beginTransaction();
        try {
            $widget = DashboardWidget::find($widgetId);

            if (!$widget) {
                return ApiResponse::error('Widget não encontrado');
            }

            $widget->update(array_filter([
                'dynamic_query_id' => $data['dynamic_query_id'] ?? null,
                'title'            => $data['title'] ?? null,
                'description'      => $data['description'] ?? null,
                'widget_type'      => $data['widget_type'] ?? null,
                'order'            => $data['order'] ?? null,
                'active'           => $data['active'] ?? null,
                'config'           => $data['config'] ?? null,
            ], fn($value) => $value !== null));

            DB::commit();
            return ApiResponse::success($widget->fresh(), 'Widget atualizado com sucesso');
        } catch (\Exception $e) {
            DB::rollBack();
            return ApiResponse::error('Erro ao atualizar widget', [$e->getMessage()]);
        }
    }

    public function deleteWidget(int $widgetId): ApiResponse
    {
        DB::beginTransaction();
        try {
            $widget = DashboardWidget::find($widgetId);

            if (!$widget) {
                return ApiResponse::error('Widget não encontrado');
            }

            $widget->delete();

            DB::commit();
            return ApiResponse::success(null, 'Widget removido com sucesso');
        } catch (\Exception $e) {
            DB::rollBack();
            return ApiResponse::error('Erro ao remover widget', [$e->getMessage()]);
        }
    }

    public function getWidgetParameters(string $widgetType): ApiResponse
    {
        return ApiResponse::success(
            WidgetParameterFactory::getParametersForWidget($widgetType)->getGrouped(),
            'Parâmetros do widget carregados'
        );
    }

    public function getWidgetData(int $widgetId, array $filterParams = []): ApiResponse
    {
        try {
            /** @var DashboardWidget $widget */
            $widget = DashboardWidget::with(['dynamicQuery', 'section'])->find($widgetId);

            if (!$widget) {
                return ApiResponse::error('Widget não encontrado');
            }

            /** @var Dashboard $dashboard */
            $dashboard = $widget->section()->first()->dashboard;

            if (!$dashboard->userHasAccess()) {
                return ApiResponse::error('Acesso negado para buscar dados da seção');
            }

            if (!$widget->active) {
                return ApiResponse::error('Widget está inativo');
            }

            if (!$widget->dynamic_query_id) {
                return ApiResponse::error('Widget não possui consulta configurada');
            }

            $queryResponse = DynamicQueryManager::executeQuery(
                $widget->dynamicQuery->key,
                $filterParams
            );

            if (!$queryResponse->isSuccess()) {
                return $queryResponse;
            }

            $data = $queryResponse->getData();

            return ApiResponse::success([
                'widget' => [
                    'id'    => $widget->id,
                    'key'   => $widget->key,
                    'title' => $widget->title,
                    'type'  => $widget->widget_type,
                ],
                'data'   => $data,
                'config' => [
                    'chart_config' => $widget->chart_config,
                ],
            ], 'Dados do widget carregados');
        } catch (\Exception $e) {
            Log::error("Erro ao carregar dados do widget {$widgetId}: " . $e->getMessage());
            return ApiResponse::error('Erro ao carregar dados do widget', [$e->getMessage()]);
        }
    }

    public function listSectionWidgets(int $sectionId): ApiResponse
    {
        try {
            $section = DashboardSection::find($sectionId);

            if (!$section) {
                return ApiResponse::error('Seção não encontrada');
            }

            $widgets = $section->widgets()->where('active', true)->orderBy('order')->get();

            return ApiResponse::success($widgets, 'Widgets da seção carregados');
        } catch (\Exception $e) {
            return ApiResponse::error('Erro ao carregar widgets da seção', [$e->getMessage()]);
        }
    }

    public function getHomeDashboard(): ApiResponse
    {
        try {
            /** @var Dashboard $dashboard */
            $dashboard = Dashboard::home()
                ->active()
                ->first();

            if (!$dashboard) {
                return ApiResponse::error("Dashboard home não encontrado");
            }

            return ApiResponse::success([
                'name' => $dashboard->name,
                'key'  => $dashboard->key
            ]);
        } catch (\Exception $e) {
            return ApiResponse::error('Erro ao carregar dashboard home', [$e->getMessage()]);
        }
    }

    public function getNavigableDashboards(): ApiResponse
    {
        try {
            $dashboards = Dashboard::query()
                ->active()
                ->navigable()
                ->select([
                    'id', // necessário para relação permission
                    'key',
                    'name',
                    'description',
                    'icon',
                    'visibility',
                    'permission_id',
                ])
                ->with('permission:id,name')
                ->get();

            $accessibleDashboards = $dashboards
                ->filter(fn ($dashboard) => $dashboard->userHasAccess())
                ->map(fn ($dashboard) => [
                    'key'         => $dashboard->key,
                    'name'        => $dashboard->name,
                    'description' => $dashboard->description,
                    'icon'        => $dashboard->icon,
                ])
                ->values();

            return ApiResponse::success(
                $accessibleDashboards,
                'Dashboards navegáveis carregados'
            );

        } catch (\Throwable $e) {

            report($e);

            return ApiResponse::error(
                'Erro ao carregar dashboards navegáveis'
            );
        }
    }



}