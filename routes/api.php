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
    Route::post('/refresh', [UserAuthController::class, 'refresh']);
    Route::post('/logout', [UserAuthController::class, 'logout']);
    Route::post('/register', [UserAuthController::class, 'register']);
});

require __DIR__ . '/subroutes/users.php';
require __DIR__ . '/subroutes/companies.php';
require __DIR__ . '/subroutes/permissions.php';
require __DIR__ . '/subroutes/parameters.php';
require __DIR__ . '/subroutes/dynamic_querys.php';
require __DIR__ . '/subroutes/services.php';
require __DIR__ . '/subroutes/integrations.php';


