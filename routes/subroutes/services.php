<?php


use App\Http\Controllers\System\ServiceManagerController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'identify.company:false'])->prefix('services')->group(function () {

    // Listar todas as consultas disponíveis
    Route::get('/', [ServiceManagerController::class, 'index']);

    Route::prefix('{serviceSlug}')->group(function () {

        // Obter parâmetros de um serviço específico
        Route::get('parameters', [ServiceManagerController::class, 'getServiceParameters']);
    });
});
