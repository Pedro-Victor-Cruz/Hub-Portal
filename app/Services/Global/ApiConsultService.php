<?php

namespace App\Services\Global;

use App\Enums\ServiceType;
use App\Services\Core\BaseGlobalService;
use App\Services\Core\ApiResponse;
use Illuminate\Support\Facades\Http;

/**
 * Serviço global para consultar APIs externas
 */
class ApiConsultService extends BaseGlobalService
{
    protected array $requiredParams = ['url'];
    protected ServiceType $serviceType = ServiceType::READ;
    protected string $serviceName = 'api_consult';
    protected string $description = 'Consulta APIs externas e retorna os dados';

    public function execute(array $params = []): ApiResponse
    {
        try {
            $this->validateParams($params);

            $url = $params['url'];
            $method = $params['method'] ?? 'GET';
            $headers = $params['headers'] ?? [];
            $body = $params['body'] ?? [];

            $response = Http::withHeaders($headers);

            $result = match(strtoupper($method)) {
                'GET' => $response->get($url, $body),
                'POST' => $response->post($url, $body),
                'PUT' => $response->put($url, $body),
                'DELETE' => $response->delete($url, $body),
                default => throw new \Exception('Método HTTP não suportado')
            };

            return $this->success(
                $result->json(),
                'API consultada com sucesso',
                [
                    'status_code' => $result->status(),
                    'response_time' => $result->transferStats?->getTransferTime()
                ]
            );

        } catch (\Exception $e) {
            return $this->error(
                'Erro ao consultar API: ' . $e->getMessage(),
                [$e->getMessage()]
            );
        }
    }
}