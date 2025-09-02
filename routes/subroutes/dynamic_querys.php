<?php


use App\Http\Controllers\System\DynamicQueryController;
use App\Http\Controllers\System\DynamicQueryFilterController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'identify.company:false'])->prefix('queries')->group(function () {

    // Listar todas as consultas disponíveis
    Route::get('/', [DynamicQueryController::class, 'index'])
        ->middleware('permission:dynamic_query.view');

    // Listar apenas as chaves das consultas
    Route::get('/keys', [DynamicQueryController::class, 'keys'])
        ->middleware('permission:dynamic_query.view');

    // Testar uma consulta sem salvar
    Route::post('/test', [DynamicQueryController::class, 'testQuery']);

    // Criar nova consulta dinâmica
    Route::post('/create', [DynamicQueryController::class, 'store'])
        ->middleware('permission:dynamic_query.create');

    // Operações específicas por chave de consulta
    Route::prefix('{key}')->group(function () {

        // Obter informações da consulta
        Route::get('/', [DynamicQueryController::class, 'show'])
            ->middleware('permission:dynamic_query.view');

        // Executar consulta dinâmica
        Route::post('/execute', [DynamicQueryController::class, 'execute']);

        // Validar se consulta pode ser executada
        Route::get('/validate', [DynamicQueryController::class, 'validateQuery'])
            ->middleware('permission:dynamic_query.view');

        // Duplicar consulta global para empresa
        Route::post('/duplicate', [DynamicQueryController::class, 'duplicate'])
            ->middleware('permission:dynamic_query.create');

        // Atualizar consulta existente
        Route::put('/update', [DynamicQueryController::class, 'update'])
            ->middleware('permission:dynamic_query.edit');

        // Remover consulta
        Route::delete('/delete', [DynamicQueryController::class, 'destroy'])
            ->middleware('permission:dynamic_query.delete');

        Route::prefix('filters')->group(function () {

            // Obter filtros da consulta
            Route::get('/', [DynamicQueryFilterController::class, 'index'])
                ->middleware('permission:dynamic_query.view');

            // Obter configurações completa dos filtros para interface
            Route::get('/config', [DynamicQueryFilterController::class, 'config'])
                ->middleware('permission:dynamic_query.view');


            // Obter sugestões de variáveis baseadas na configuração da consulta
            Route::get('/variable-suggestions', [DynamicQueryFilterController::class, 'variableSuggestions'])
                ->middleware('permission:dynamic_query.view');

            // Criar novo filtro
            Route::post('/create', [DynamicQueryFilterController::class, 'store'])
                ->middleware('permission:dynamic_query.create');

            // Atualizar filtro existente
            Route::put('/{varName}/update', [DynamicQueryFilterController::class, 'update'])
                ->middleware('permission:dynamic_query.edit');

            // Remover filtro
            Route::delete('/{varName}/delete', [DynamicQueryFilterController::class, 'destroy'])
                ->middleware('permission:dynamic_query.delete');

            // Reordenar filtros
            Route::put('/reorder', [DynamicQueryFilterController::class, 'reorder'])
                ->middleware('permission:dynamic_query.edit');

        });

    });

    Route::prefix('filters')->group(function () {

        // Obter tipos de filtros disponíveis (sem chave de consulta)
        Route::get('/types', [DynamicQueryFilterController::class, 'filterTypes'])
            ->middleware('permission:dynamic_query.view');

        // Obter template para criação de filtro baseado no tipo (sem chave de consulta)
        Route::get('/template/{type}', [DynamicQueryFilterController::class, 'filterTemplate'])
            ->middleware('permission:dynamic_query.create');

    });

});