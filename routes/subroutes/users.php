<?php

use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {

    Route::get('/profile', [UserController::class, 'profile']);
    Route::put('/profile/update', [UserController::class, 'profileUpdate']);

    Route::prefix('user')->group(function () {

        Route::get('/', [UserController::class, 'index'])
            ->middleware('permission:user.view');

        Route::get('/{id}', [UserController::class, 'show'])
            ->middleware('permission:user.view');

        Route::post('/create', [UserController::class, 'store'])
            ->middleware('permission:user.create');

        Route::put('/{id}/update', [UserController::class, 'update'])
            ->middleware('permission:user.edit');

        Route::delete('/{id}/delete', [UserController::class, 'destroy'])
            ->middleware('permission:user.delete');

    });
});