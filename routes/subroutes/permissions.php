<?php

use App\Http\Controllers\PermissionController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {

    Route::prefix('permission')->group(function () {

        Route::get('access-levels', [PermissionController::class, 'accessLevels']);

        Route::get('/', [PermissionController::class, 'permissions'])
            ->middleware('permission:permission.view');

        Route::post('/assign-permissions/{userid}', [PermissionController::class, 'assignPermissionsToUser'])
            ->middleware('permission:permission.assign');

        Route::prefix('group')->group(function () {

            Route::get('/', [PermissionController::class, 'groups'])
                ->middleware('permission:permission_group.view');

            Route::get('/{idGroup}', [PermissionController::class, 'findGroup']);

            Route::post('/create', [PermissionController::class, 'createGroup'])
                ->middleware('permission:permission_group.create');

            Route::put('/{idGroup}/update', [PermissionController::class, 'updateGroup'])
                ->middleware('permission:permission_group.update');

            Route::delete('/{idGroup}/delete', [PermissionController::class, 'deleteGroup'])
                ->middleware('permission:permission_group.delete');

            Route::post('assign-group/{userId}', [PermissionController::class, 'assignGroupToUser'])
                ->middleware('permission:permission_group.assign');
        });
    });
});