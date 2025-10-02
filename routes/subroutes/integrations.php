<?php

use App\Http\Controllers\Company\CompanyController;
use App\Http\Controllers\System\IntegrationsController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {

    Route::prefix('integration')->group(function () {

        Route::get('/', [IntegrationsController::class, 'availableIntegrations']);

        Route::get('/{integration_name}/info', [IntegrationsController::class, 'integrationInfo'])
            ->middleware('permission:company.view');

        Route::get('/{integration_name}', [IntegrationsController::class, 'getCompanyIntegration'])
            ->middleware(['permission:company.view', 'identify.company']);

        Route::get('/{integration_id}/test-connection', [IntegrationsController::class, 'testConnection'])
            ->middleware('permission:integration.manage');

        Route::post('/create', [IntegrationsController::class, 'create'])
            ->middleware('permission:integration.manage');

        Route::put('/{id}/update', [IntegrationsController::class, 'update'])
            ->middleware('permission:integration.manage');

        Route::delete('/{id}/delete', [IntegrationsController::class, 'destroy'])
            ->middleware('permission:integration.manage');

    });
});