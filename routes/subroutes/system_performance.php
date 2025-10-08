<?php

use App\Http\Controllers\System\SystemPerformanceController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {

    // Rotas de performance do sistema (apenas para admins)
    Route::middleware(['permission:system_performance.view'])->prefix('performance')->group(function () {

        // Dashboard principal de performance
        Route::get('/dashboard', [SystemPerformanceController::class, 'dashboard']);

        // Métricas de performance detalhadas
        Route::get('/metrics', [SystemPerformanceController::class, 'metrics']);

        // Análise detalhada de um endpoint específico
        Route::get('/endpoint-analysis', [SystemPerformanceController::class, 'endpointAnalysis']);

        // Análise de queries do banco de dados
        Route::get('/query-analysis', [SystemPerformanceController::class, 'queryAnalysis']);

        // Saúde do sistema em tempo real
        Route::get('/system-health', [SystemPerformanceController::class, 'systemHealth']);

        // Histórico de saúde do sistema
        Route::get('/health-history', [SystemPerformanceController::class, 'healthHistory']);

        // Comparação de performance entre períodos
        Route::get('/compare-performance', [SystemPerformanceController::class, 'comparePerformance']);

        // Relatório de performance (exportável)
        Route::get('/generate-report', [SystemPerformanceController::class, 'generateReport']);

    });

    // Limpeza de métricas de performance (apenas para super admins)
    Route::middleware(['permission:system_performance.delete'])->group(function () {
        Route::delete('/performance/cleanup', [SystemPerformanceController::class, 'cleanup']);
    });

});