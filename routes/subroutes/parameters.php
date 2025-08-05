<?php

use App\Http\Controllers\System\ParameterController;
use Illuminate\Support\Facades\Route;

Route::prefix('parameter')->group(function () {

    Route::get('/categories', [ParameterController::class, 'listCategories']);

    Route::middleware('auth')->group(function () {

        Route::get('/', [ParameterController::class, 'index'])
            ->middleware('permission:parameter.view');

        Route::post('/create', [ParameterController::class, 'store'])
            ->middleware('permission:parameter.create');

        Route::put('/{id}/update', [ParameterController::class, 'update'])
            ->middleware('permission:parameter.update');

        Route::put('/{id}/company/update', [ParameterController::class, 'updateValueCompany'])
            ->middleware('permission:parameter.company.update');

        Route::delete('/{id}/delete', [ParameterController::class, 'destroy'])
            ->middleware('permission:parameter.delete');
    });

});