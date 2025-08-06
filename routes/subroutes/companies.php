<?php

use App\Http\Controllers\Company\CompanyController;
use App\Http\Controllers\Erp\ErpController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {

    Route::prefix('company')->group(function () {

        Route::get('/', [CompanyController::class, 'index'])
            ->middleware('permission:company.view');

        Route::get('/{id}', [CompanyController::class, 'show'])
            ->middleware('permission:company.view');

        Route::post('/create', [CompanyController::class, 'store'])
            ->middleware('permission:company.create');

        Route::put('/{id}/update', [CompanyController::class, 'update'])
            ->middleware('permission:company.edit');

        Route::delete('/{id}/delete', [CompanyController::class, 'destroy'])
            ->middleware('permission:company.delete');

        Route::prefix('erp-settings')->group(function () {

            Route::get('/{idCompany}', [ErpController::class, 'showErpSettings'])
                ->middleware('permission:company.erp_settings.view');

            Route::post('/create', [ErpController::class, 'createErpSettings'])
                ->middleware('permission:company.erp_settings.create');

            Route::put('/{id}/update', [ErpController::class, 'updateErpSettings'])
                ->middleware('permission:company.erp_settings.edit');

            Route::delete('/{id}/delete', [ErpController::class, 'destroyErpSettings'])
                ->middleware('permission:company.erp_settings.delete');

            Route::get('/{companyId}/test-connection', [ErpController::class, 'testConnection']);

        });

    });
});