<?php

namespace App\Http\Controllers\System;

use App\Enums\ServiceType;
use App\Facades\ServiceManager;
use App\Http\Controllers\Controller;
use App\Services\Core\ApiResponse;
use Exception;
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
        $serviceType = $request->get('service_type');

        if ($serviceType) {
            $serviceType = ServiceType::tryFrom($serviceType);

            if (!$serviceType) {
                return ApiResponse::error("Tipo de serviço inválido.")->toJson();
            }

            $services = ServiceManager::getServicesByType($serviceType, $company);
            return ApiResponse::success($services, "Lista de serviços")->toJson();
        }

        return ApiResponse::error("Tipo de serviço inválido ou não especificado.")->toJson();
    }

    /**
     * Busca os parâmetros de um serviço específico
     * GET /api/services/{serviceSlug}/parameters
     */
    public function getServiceParameters(string $serviceSlug): JsonResponse
    {
        try {
            $instance = ServiceManager::getServiceInstance($serviceSlug);
            $parameters = $instance->getGroupedParameters();

            return ApiResponse::success($parameters, "Parâmetros do serviço")->toJson();
        } catch (Exception $exception) {
            return ApiResponse::error(
                "Erro ao buscar parâmetros do serviço: {$exception->getMessage()}",
                ['exception_type' => get_class($exception)]
            )->toJson();
        }
    }

}