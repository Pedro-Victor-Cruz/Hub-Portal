<?php

use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\PortalController;
use App\Http\Controllers\UserAuthController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::prefix('auth')->group(function () {
    Route::post('/', [UserAuthController::class, 'auth']);
    Route::post('/refresh', [UserAuthController::class, 'refresh']);
    Route::post('/logout', [UserAuthController::class, 'logout']);
    Route::post('/register', [UserAuthController::class, 'register']);
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [UserController::class, 'index']);
    Route::put('/profile/update', [UserController::class, 'update']);

    /**
     * Controller PortalController
    */
    Route::prefix('portal')->group(function () {
        Route::get('/', [PortalController::class, 'index']);
        Route::post('/create', [PortalController::class, 'create']);
        Route::get('/{id}', [PortalController::class, 'show']);
        Route::put('/{id}/update', [PortalController::class, 'update']);
        Route::delete('/{id}/delete', [PortalController::class, 'delete']);
    });

    /**
     * Rotas que necessitam informar o slug do portal
    */
    Route::prefix('{slug_portal}')->group(function () {

        /**
         * Controller DepartmentController
        */
        Route::prefix('department')->group(function () {
            Route::get('/', [DepartmentController::class, 'index']);
            Route::get('/{id}', [DepartmentController::class, 'show']);
            Route::post('/create', [DepartmentController::class, 'create']);
            Route::put('/{id}/update', [DepartmentController::class, 'update']);
            Route::delete('/{id}/delete', [DepartmentController::class, 'delete']);
        });

    });
});