<?php

namespace App\Http\Controllers\System;

use App\Enums\ServiceType;
use App\Facades\ServiceManager;
use App\Http\Controllers\Controller;
use App\Services\Core\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Controller para gerenciar e executar consultas dinâmicas
 */
class ServiceManagerController extends Controller
{

    /**
     * Lista todos os serviços disponíveis
     * GET /api/services
     */
    public function index(Request $request): JsonResponse
    {
        $company = $request->get('company');
        $services = ServiceManager::getServicesByType(ServiceType::QUERY, $company);

        return ApiResponse::success($services, "Lista de serviços")->toJson();
    }
}