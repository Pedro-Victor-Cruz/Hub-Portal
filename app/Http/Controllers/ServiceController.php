<?php

namespace App\Http\Controllers;

use App\Services\EntityService;
use App\Services\ServiceManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ServiceController extends Controller
{

    protected $serviceManager;

    public function __construct(ServiceManager $serviceManager)
    {
        $this->serviceManager = $serviceManager;
    }

    public function handleService(Request $request): JsonResponse
    {
        $serviceName = $request->input('serviceName');
        $requestBody = $request->input('requestBody', []);

        try {
            $response = $this->serviceManager->executeService($serviceName, $requestBody);
            return response()->json($response, 200);
        } catch (\Exception $e) {
            return response()->json([
                'serviceName' => $serviceName,
                'status' => 'error',
                'statusMessage' => $e->getMessage(),
                'responseBody' => [],
            ], 400);
        }
    }
}
