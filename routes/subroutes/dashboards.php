<?php

use App\Http\Controllers\System\DashboardController;
use App\Http\Controllers\System\DashboardFilterController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->prefix('dashboards')->group(function () {

    // Rotas principais do dashboard
    Route::prefix('/')->group(function () {
        // Lista todos os dashboards
        Route::get('/', [DashboardController::class, 'index']);

        // Cria um novo dashboard
        Route::post('/create', [DashboardController::class, 'store']);

        // Duplica um dashboard
        Route::post('/{key}/duplicate', [DashboardController::class, 'duplicate']);
    });

    // Rotas específicas por dashboard
    Route::prefix('/{key}')->group(function () {
        // Obtém a estrutura completa de um dashboard
        Route::get('/', [DashboardController::class, 'show']);

        // Atualiza um dashboard existente
        Route::put('/update', [DashboardController::class, 'update']);

        // Remove um dashboard
        Route::delete('/delete', [DashboardController::class, 'destroy']);

        // Rotas de seções
        Route::prefix('/sections')->group(function () {
            // Cria uma nova seção em um dashboard
            Route::post('/create', [DashboardController::class, 'createSection']);
        });

        // Rotas de filtros
        Route::prefix('/filters')->group(function () {
            // Lista filtros de um dashboard
            Route::get('/', [DashboardFilterController::class, 'index']);

            // Cria um novo filtro
            Route::post('/create', [DashboardFilterController::class, 'store']);

            // Reordena filtros
            Route::put('/reorder', [DashboardFilterController::class, 'reorder']);

            // Obtém configuração completa dos filtros
            Route::get('/config', [DashboardFilterController::class, 'config']);

            Route::get('/variable-suggestions', [DashboardFilterController::class, 'variableSuggestions']);

            // Rotas específicas por filtro
            Route::prefix('/{varName}')->group(function () {
                // Atualiza um filtro existente
                Route::put('/update', [DashboardFilterController::class, 'update']);

                // Remove um filtro
                Route::delete('/delete', [DashboardFilterController::class, 'destroy']);
            });
        });
    });

    Route::prefix('filters')->group(function () {

        // Obter tipos de filtros disponíveis (sem chave de consulta)
        Route::get('/types', [DashboardFilterController::class, 'filterTypes']);

    });

    // Rotas independentes de seções (não dependem de dashboard key no path)
    Route::prefix('/sections')->group(function () {
        // Atualiza uma seção
        Route::put('/{sectionId}/update', [DashboardController::class, 'updateSection']);

        // Remove uma seção
        Route::delete('/{sectionId}/delete', [DashboardController::class, 'deleteSection']);

        // Obtém dados de todos os widgets de uma seção
        Route::get('/{sectionId}/data', [DashboardController::class, 'getSectionData']);

        // Listar todos os widgets de uma seção
        Route::get('/{sectionId}/widgets', [DashboardController::class, 'listSectionWidgets']);

        // Cria um novo widget em uma seção
        Route::post('/{sectionId}/widgets/create', [DashboardController::class, 'createWidget']);

    });

    // Rotas independentes de widgets (não dependem de dashboard key no path)
    Route::prefix('/widgets')->group(function () {
        // Atualiza um widget
        Route::put('/{widgetId}/update', [DashboardController::class, 'updateWidget']);

        // Remove um widget
        Route::delete('/{widgetId}/delete', [DashboardController::class, 'deleteWidget']);

        // Obtém dados de um widget específico
        Route::post('/{widgetId}/data', [DashboardController::class, 'getWidgetData']);

        // Obtém os parâmetros do widget
        Route::get('/{widgetType}/parameters', [DashboardController::class, 'getParametersWidget']);
    });

});