<?php


use App\Http\Controllers\System\ServiceManagerController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'identify.company:false'])->prefix('services')->group(function () {

    // Listar todas as consultas disponíveis
    Route::get('/', [ServiceManagerController::class, 'index']);
});
