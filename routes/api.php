<?php

use App\Http\Controllers\User\UserAuthController;
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
    Route::post('/logout', [UserAuthController::class, 'logout']);
    Route::middleware('auth')->group(function () {
        Route::post('/refresh', [UserAuthController::class, 'refresh']);
    });
});

require __DIR__ . '/subroutes/users.php';
require __DIR__ . '/subroutes/permissions.php';
require __DIR__ . '/subroutes/parameters.php';
require __DIR__ . '/subroutes/dynamic_querys.php';
require __DIR__ . '/subroutes/services.php';
require __DIR__ . '/subroutes/integrations.php';
require __DIR__ . '/subroutes/logs_system.php';
require __DIR__ . '/subroutes/system_performance.php';
require __DIR__ . '/subroutes/dashboards.php';


