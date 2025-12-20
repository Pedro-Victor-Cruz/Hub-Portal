<?php

use App\Http\Controllers\System\DashboardController;
use App\Http\Controllers\System\DashboardFilterController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->prefix('dashboards')->group(function () {

    // Rotas principais do dashboard
    Route::prefix('/')->group(function () {
        // Lista todos os dashboards
        Route::get('/', [DashboardController::class, 'index'])
            ->middleware('permission:dashboard.view');

        // Cria um novo dashboard
        Route::post('/create', [DashboardController::class, 'store'])
            ->middleware('permission:dashboard.create');

        // Duplica um dashboard
        Route::post('/{key}/duplicate', [DashboardController::class, 'duplicate']);

        Route::get('/navigable/list', [DashboardController::class, 'getNavigableDashboards']);

        Route::get('/home/list', [DashboardController::class, 'getHomeDashboard']);
    });

    // Rotas específicas por dashboard
    Route::prefix('/{key}')->group(function () {
        // Obtém a estrutura completa de um dashboard
        Route::get('/', [DashboardController::class, 'show']);

        // Atualiza um dashboard existente
        Route::put('/update', [DashboardController::class, 'update'])
            ->middleware('permission:dashboard.edit');

        // Remove um dashboard
        Route::delete('/delete', [DashboardController::class, 'destroy'])
            ->middleware('permission:dashboard.delete');

        // Rotas de seções
        Route::prefix('/sections')->group(function () {
            // Cria uma nova seção em um dashboard
            Route::post('/create', [DashboardController::class, 'createSection'])
                ->middleware('permission:dashboard.create');
        });

        // Rotas de filtros
        Route::prefix('/filters')->group(function () {
            // Lista filtros de um dashboard
            Route::get('/', [DashboardFilterController::class, 'index'])
                ->middleware('permission:dashboard.view');

            // Cria um novo filtro
            Route::post('/create', [DashboardFilterController::class, 'store'])
                ->middleware('permission:dashboard.create');

            // Reordena filtros
            Route::put('/reorder', [DashboardFilterController::class, 'reorder'])
                ->middleware('permission:dashboard.create');

            // Obtém configuração completa dos filtros
            Route::get('/config', [DashboardFilterController::class, 'config'])
                ->middleware('permission:dashboard.view');

            Route::get('/variable-suggestions', [DashboardFilterController::class, 'variableSuggestions'])
                ->middleware('permission:dashboard.view');

            // Rotas específicas por filtro
            Route::prefix('/{varName}')->group(function () {
                // Atualiza um filtro existente
                Route::put('/update', [DashboardFilterController::class, 'update'])
                    ->middleware('permission:dashboard.edit');

                // Remove um filtro
                Route::delete('/delete', [DashboardFilterController::class, 'destroy'])
                    ->middleware('permission:dashboard.delete');
            });
        });
    });

    // Rotas independentes de seções (não dependem de dashboard key no path)
    Route::prefix('/sections')->group(function () {
        // Atualiza uma seção
        Route::put('/{sectionId}/update', [DashboardController::class, 'updateSection'])
            ->middleware('permission:dashboard.edit');

        // Remove uma seção
        Route::delete('/{sectionId}/delete', [DashboardController::class, 'deleteSection'])
            ->middleware('permission:dashboard.delete');

        // Obtém dados de todos os widgets de uma seção
        Route::post('/{sectionId}/data', [DashboardController::class, 'getSectionData']);

        // Listar todos os widgets de uma seção
        Route::get('/{sectionId}/widgets', [DashboardController::class, 'listSectionWidgets'])
            ->middleware('permission:dashboard.view');

        // Cria um novo widget em uma seção
        Route::post('/{sectionId}/widgets/create', [DashboardController::class, 'createWidget'])
            ->middleware('permission:dashboard.create');

    });

    Route::prefix('filters')->group(function () {

        // Obter tipos de filtros disponíveis (sem chave de consulta)
        Route::get('/types', [DashboardFilterController::class, 'filterTypes']);

    });

    // Rotas independentes de widgets (não dependem de dashboard key no path)
    Route::prefix('/widgets')->group(function () {
        // Atualiza um widget
        Route::put('/{widgetId}/update', [DashboardController::class, 'updateWidget'])
            ->middleware('permission:dashboard.update');

        // Remove um widget
        Route::delete('/{widgetId}/delete', [DashboardController::class, 'deleteWidget'])
            ->middleware('permission:dashboard.delete');

        // Obtém dados de um widget específico
        Route::post('/{widgetId}/data', [DashboardController::class, 'getWidgetData']);

        // Obtém os parâmetros do widget
        Route::get('/{widgetType}/parameters', [DashboardController::class, 'getParametersWidget'])
            ->middleware('permission:dashboard.view');
    });

});