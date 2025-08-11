<?php

namespace App\Services\Global;

use App\Enums\ServiceType;
use App\Services\Core\ApiResponse;
use App\Services\Core\BaseGlobalService;
use App\Services\Parameter\ServiceParameter;
use Illuminate\Support\Facades\Http;

/**
 * Serviço global para consultar APIs externas
 */
class ApiConsultService extends BaseGlobalService
{
    protected ServiceType $serviceType = ServiceType::QUERY;
    protected string $serviceName = 'api_consult';
    protected string $description = 'Consulta APIs externas e retorna os dados';

    public function execute(array $params = []): ApiResponse
    {
        try {
            $validatedParams = $this->validateAndSanitizeParams($params);

            $url = $validatedParams['url'];
            $method = $validatedParams['method'] ?? 'GET';
            $headers = $validatedParams['headers'] ?? [];
            $body = $validatedParams['body'] ?? [];

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

    protected function configureParameters(): void
    {
        $this->parameterManager->addMany([
            // Parâmetro obrigatório: URL da API
            ServiceParameter::url(
                name: 'url',
                required: true,
                description: 'URL da API a ser consultada',
            )->withLabel('URL da API')->withPlaceholder('https://api.exemplo.com/endpoint'),

            // Parâmetro opcional: Método HTTP
            ServiceParameter::select(
                name: 'method',
                options: ['GET', 'POST', 'PUT', 'DELETE'],
                defaultValue: 'GET',
                description: 'Método HTTP a ser utilizado na consulta'
            )->withLabel('Método HTTP'),

            // Parâmetro opcional: Cabeçalhos HTTP
            ServiceParameter::object(
                name: 'headers',
                defaultValue: [],
                description: 'Cabeçalhos HTTP a serem enviados na consulta',
                properties: [
                    'Content-Type' => ServiceParameter::text(
                        name: 'Content-Type',
                        defaultValue: 'application/json',
                        description: 'Tipo de conteúdo do corpo da requisição'
                    ),
                    'Authorization' => ServiceParameter::text(
                        name: 'Authorization',
                        description: 'Token de autenticação, se necessário'
                    )
                ]
            )->withLabel('Cabeçalhos HTTP'),

            // Parâmetro opcional: Corpo da requisição
            ServiceParameter::object(
                name: 'body',
                defaultValue: [],
                description: 'Corpo da requisição para métodos POST/PUT'
            )->withLabel('Corpo da Requisição')
        ]);
    }
}