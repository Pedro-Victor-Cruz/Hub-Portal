<?php


use App\Http\Controllers\System\DynamicQueryController;
use App\Models\DynamicQuery;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'identify.company:false'])->prefix('queries')->group(function () {

    // Listar todas as consultas disponíveis
    Route::get('/', [DynamicQueryController::class, 'index']);

    // Listar apenas as chaves das consultas
    Route::get('/keys', [DynamicQueryController::class, 'keys']);

    // Testar uma consulta sem salvar
    Route::post('/test', [DynamicQueryController::class, 'testQuery']);

    // Criar nova consulta dinâmica
    Route::post('/create', [DynamicQueryController::class, 'store']);

    // Operações específicas por chave de consulta
    Route::prefix('{key}')->group(function () {

        // Obter informações da consulta
        Route::get('/', [DynamicQueryController::class, 'show']);

        // Executar consulta dinâmica
        Route::post('/execute', [DynamicQueryController::class, 'execute']);

        // Validar se consulta pode ser executada
        Route::get('/validate', [DynamicQueryController::class, 'validateQuery']);

        // Duplicar consulta global para empresa
        Route::post('/duplicate', [DynamicQueryController::class, 'duplicate']);

        // Atualizar consulta existente
        Route::put('/update', [DynamicQueryController::class, 'update']);

        // Remover consulta
        Route::delete('/delete', [DynamicQueryController::class, 'destroy']);

    });

});

/*
|--------------------------------------------------------------------------
| Rotas públicas para consultas globais (sem autenticação)
|--------------------------------------------------------------------------
| Estas rotas permitem acesso a consultas globais básicas
| Útil para integrações externas ou dashboards públicos
|
*/

Route::prefix('public/queries')->group(function () {

    // Listar consultas globais públicas
    Route::get('/', function () {
        $queries = DynamicQuery::global()->active()->get(['key', 'name', 'description']);
        return response()->json([
            'success' => true,
            'data' => $queries
        ]);
    })->name('queries.public.index');

});