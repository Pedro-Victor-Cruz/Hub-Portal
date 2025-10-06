<?php

use App\Http\Controllers\System\LogViewerController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {


        // Rotas de visualização de logs (apenas para admins)
        Route::middleware(['permission:log.view'])->prefix('logs')->group(function () {

            // Listagem de logs com filtros
            Route::get('/', [LogViewerController::class, 'index']);

            // Detalhes de um log específico
            Route::get('/{id}', [LogViewerController::class, 'show']);

            // Estatísticas dos logs
            Route::get('/statistics/dashboard', [LogViewerController::class, 'statistics']);

            // Timeline de atividades de um usuário
            Route::get('/user/{userId}/timeline', [LogViewerController::class, 'userTimeline']);

            // Histórico de um model específico
            Route::get('/model/history', [LogViewerController::class, 'modelHistory']);

            // Logs de um batch específico
            Route::get('/batch/{batchId}', [LogViewerController::class, 'batchLogs']);

        });

        // Limpeza de logs (apenas para super admins)
        Route::middleware(['permission:log.delete'])->group(function () {
            Route::delete('/logs/cleanup', [LogViewerController::class, 'cleanup']);
        });


});