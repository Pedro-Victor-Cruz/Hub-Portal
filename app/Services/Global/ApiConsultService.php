<?php

namespace App\Services\Global;

use App\Enums\ServiceType;
use App\Services\Core\ApiResponse;
use App\Services\Core\BaseGlobalService;
use App\Services\Core\BaseService;
use App\Services\Parameter\ServiceParameter;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\Response;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\ConnectionException;

/**
 * Serviço global para consultar APIs externas
 */
class ApiConsultService extends BaseService
{
    protected ServiceType $serviceType = ServiceType::QUERY;
    protected string $serviceName = 'Consulta de API Externa';
    protected string $description = 'Consulta uma API externa via HTTP com suporte a diversos métodos, cabeçalhos, corpo, timeout e tratamento de erros.';

    public function execute(array $params = []): ApiResponse
    {
        try {
            $validatedParams = $this->validateAndSanitizeParams($params);

            $url = $validatedParams['url'];
            $method = strtoupper($validatedParams['method'] ?? 'GET');
            $headers = $validatedParams['headers'] ?? [];
            $body = $validatedParams['body'] ?? [];
            $timeout = $validatedParams['timeout'] ?? 30;
            $retryTimes = $validatedParams['retry_times'] ?? 0;
            $retryDelay = $validatedParams['retry_delay'] ?? 1000;
            $followRedirects = $validatedParams['follow_redirects'] ?? true;
            $verifySSL = $validatedParams['verify_ssl'] ?? true;


            // Configura o cliente HTTP
            $httpClient = Http::timeout($timeout)
                ->withOptions([
                    'verify' => $verifySSL,
                    'allow_redirects' => $followRedirects ? ['max' => 10] : false
                ])
                ->withHeaders($headers);

            // Configura retry se especificado
            if ($retryTimes > 0) {
                $httpClient = $httpClient->retry($retryTimes, $retryDelay);
            }

            $startTime = microtime(true);

            // Executa a requisição
            $response = $this->makeRequest($httpClient, $method, $url, $body);

            $endTime = microtime(true);
            $responseTime = ($endTime - $startTime) * 1000; // em millisegundos

            // Analisa a resposta
            $responseAnalysis = $this->analyzeResponse($response);

            // Se a resposta não é considerada sucesso, retorna erro
            if (!$responseAnalysis['is_success']) {
                return $this->error(
                    $responseAnalysis['error_message'],
                    $responseAnalysis['errors'],
                    [
                        'request_info' => $this->getRequestInfo($method, $url, $headers, $body),
                        'response_info' => $responseAnalysis['response_info'],
                        'response_time_ms' => round($responseTime, 2)
                    ]
                );
            }

            // Resposta de sucesso
            return $this->success(
                $responseAnalysis['data'],
                'API consultada com sucesso',
                [
                    'request_info' => $this->getRequestInfo($method, $url, $headers, $body),
                    'response_info' => $responseAnalysis['response_info'],
                    'response_time_ms' => round($responseTime, 2)
                ]
            );

        } catch (ConnectionException $e) {
            return $this->error(
                'Erro de conexão com a API: ' . $e->getMessage(),
                ['connection_error', $e->getMessage()],
                [
                    'request_info' => $this->getRequestInfo($method ?? 'GET', $url ?? '', $headers ?? [], $body ?? []),
                    'error_type' => 'connection_error'
                ]
            );
        } catch (RequestException $e) {
            return $this->error(
                'Erro na requisição HTTP: ' . $e->getMessage(),
                ['request_error', $e->getMessage()],
                [
                    'request_info' => $this->getRequestInfo($method ?? 'GET', $url ?? '', $headers ?? [], $body ?? []),
                    'error_type' => 'request_error'
                ]
            );
        } catch (\Exception $e) {
            return $this->error(
                'Erro inesperado ao consultar API: ' . $e->getMessage(),
                ['unexpected_error', $e->getMessage()],
                [
                    'request_info' => $this->getRequestInfo($method ?? 'GET', $url ?? '', $headers ?? [], $body ?? []),
                    'error_type' => 'unexpected_error'
                ]
            );
        }
    }

    /**
     * Executa a requisição HTTP baseada no método
     */
    private function makeRequest($httpClient, string $method, string $url, array $body): Response
    {
        return match($method) {
            'GET' => $httpClient->get($url, $body),
            'POST' => $httpClient->post($url, $body),
            'PUT' => $httpClient->put($url, $body),
            'PATCH' => $httpClient->patch($url, $body),
            'DELETE' => $httpClient->delete($url, $body),
            'HEAD' => $httpClient->head($url),
            'OPTIONS' => $httpClient->send('OPTIONS', $url),
            default => throw new \Exception("Método HTTP '{$method}' não suportado")
        };
    }

    /**
     * Analisa a resposta da API
     */
    private function analyzeResponse(Response $response): array
    {
        $statusCode = $response->status();
        $responseBody = $response->body();
        $responseHeaders = $response->headers();

        // Define códigos de status considerados sucesso
        $successCodes = [200, 201, 202, 204];

        $isSuccess = in_array($statusCode, $successCodes);

        // Tenta decodificar JSON, senão retorna o corpo raw
        $data = null;
        $contentType = $response->header('Content-Type') ?? '';

        if (str_contains(strtolower($contentType), 'application/json')) {
            $data = $response->json();
        } else {
            $data = $responseBody;
        }

        $responseInfo = [
            'status_code' => $statusCode,
            'status_text' => $this->getStatusText($statusCode),
            'content_type' => $contentType,
            'content_length' => strlen($responseBody)
        ];

        if (!$isSuccess) {
            $errorMessage = $this->generateErrorMessage($statusCode, $data, $responseBody);

            return [
                'is_success' => false,
                'error_message' => $errorMessage,
                'errors' => [
                    'http_error',
                    "HTTP {$statusCode}",
                    $this->getStatusText($statusCode),
                    $errorMessage
                ],
                'response_info' => $responseInfo,
                'data' => $data
            ];
        }

        return [
            'is_success' => true,
            'data' => $data,
            'response_info' => $responseInfo
        ];
    }

    /**
     * Gera mensagem de erro baseada na resposta
     */
    private function generateErrorMessage(int $statusCode, $data, string $rawBody): string
    {
        $statusText = $this->getStatusText($statusCode);

        // Tenta extrair mensagem de erro do corpo da resposta
        $errorMessage = "HTTP {$statusCode} - {$statusText}";

        if (is_array($data)) {
            // Procura por campos comuns de erro
            $errorFields = ['error', 'message', 'detail', 'error_description', 'msg'];
            foreach ($errorFields as $field) {
                if (isset($data[$field]) && is_string($data[$field])) {
                    $errorMessage .= " - " . $data[$field];
                    break;
                }
            }
        } elseif (is_string($data) && !empty(trim($data))) {
            $errorMessage .= " - " . trim($data);
        } elseif (!empty(trim($rawBody))) {
            $errorMessage .= " - " . trim($rawBody);
        }

        return $errorMessage;
    }

    /**
     * Retorna o texto descritivo do código de status HTTP
     */
    private function getStatusText(int $code): string
    {
        $statusTexts = [
            // 2xx Success
            200 => 'OK',
            201 => 'Created',
            202 => 'Accepted',
            204 => 'No Content',

            // 3xx Redirection
            301 => 'Moved Permanently',
            302 => 'Found',
            304 => 'Not Modified',

            // 4xx Client Error
            400 => 'Bad Request',
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            406 => 'Not Acceptable',
            408 => 'Request Timeout',
            409 => 'Conflict',
            410 => 'Gone',
            422 => 'Unprocessable Entity',
            429 => 'Too Many Requests',

            // 5xx Server Error
            500 => 'Internal Server Error',
            501 => 'Not Implemented',
            502 => 'Bad Gateway',
            503 => 'Service Unavailable',
            504 => 'Gateway Timeout',
        ];

        return $statusTexts[$code] ?? 'Unknown Status';
    }

    /**
     * Retorna informações sobre a requisição feita
     */
    private function getRequestInfo(string $method, string $url, array $headers, array $body): array
    {
        // Remove dados sensíveis dos headers e body para log
        $safeHeaders = $this->sanitizeHeaders($headers);
        $safeBody = $this->sanitizeBody($body);

        return [
            'method' => $method,
            'url' => $url,
            'headers' => $safeHeaders,
            'body' => $safeBody
        ];
    }

    /**
     * Remove dados sensíveis dos headers
     */
    private function sanitizeHeaders(array $headers): array
    {
        $sensitiveKeys = ['authorization', 'x-api-key', 'token', 'password', 'secret'];
        $sanitized = [];

        foreach ($headers as $key => $value) {
            $keyLower = strtolower($key);
            $isSensitive = false;

            foreach ($sensitiveKeys as $sensitiveKey) {
                if (str_contains($keyLower, $sensitiveKey)) {
                    $isSensitive = true;
                    break;
                }
            }

            $sanitized[$key] = $isSensitive ? '***HIDDEN***' : $value;
        }

        return $sanitized;
    }

    /**
     * Remove dados sensíveis do body
     */
    private function sanitizeBody(array $body): array
    {
        if (empty($body)) {
            return $body;
        }

        $sensitiveKeys = ['password', 'token', 'secret', 'key', 'authorization'];
        $sanitized = [];

        foreach ($body as $key => $value) {
            $keyLower = strtolower($key);
            $isSensitive = false;

            foreach ($sensitiveKeys as $sensitiveKey) {
                if (str_contains($keyLower, $sensitiveKey)) {
                    $isSensitive = true;
                    break;
                }
            }

            if ($isSensitive) {
                $sanitized[$key] = '***HIDDEN***';
            } elseif (is_array($value)) {
                $sanitized[$key] = $this->sanitizeBody($value);
            } else {
                $sanitized[$key] = $value;
            }
        }

        return $sanitized;
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
                options: ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'HEAD', 'OPTIONS'],
                defaultValue: 'GET',
                description: 'Método HTTP a ser utilizado na consulta'
            )->withLabel('Método HTTP'),

            // Parâmetro opcional: Timeout
            ServiceParameter::number(
                name: 'timeout',
                defaultValue: 30,
                description: 'Timeout da requisição em segundos',
                min: 1,
                max: 300
            )->withLabel('Timeout (segundos)'),

            // Parâmetro opcional: Tentativas de retry
            ServiceParameter::number(
                name: 'retry_times',
                defaultValue: 0,
                description: 'Número de tentativas em caso de falha',
                min: 0,
                max: 5
            )->withLabel('Tentativas de Retry'),

            // Parâmetro opcional: Delay entre retries
            ServiceParameter::number(
                name: 'retry_delay',
                defaultValue: 1000,
                description: 'Delay entre tentativas em milissegundos',
                min: 100,
                max: 10000
            )->withLabel('Delay entre Retries (ms)'),

            // Parâmetro opcional: Seguir redirecionamentos
            ServiceParameter::boolean(
                name: 'follow_redirects',
                defaultValue: true,
                description: 'Seguir redirecionamentos HTTP automaticamente'
            )->withLabel('Seguir Redirecionamentos'),

            // Parâmetro opcional: Verificar SSL
            ServiceParameter::boolean(
                name: 'verify_ssl',
                defaultValue: true,
                description: 'Verificar certificados SSL/TLS'
            )->withLabel('Verificar SSL'),

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
                        description: 'Token de autenticação (ex: Bearer token123)'
                    ),
                    'User-Agent' => ServiceParameter::text(
                        name: 'User-Agent',
                        defaultValue: 'ApiConsultService/1.0',
                        description: 'User Agent para identificação'
                    ),
                    'Accept' => ServiceParameter::text(
                        name: 'Accept',
                        defaultValue: 'application/json',
                        description: 'Tipos de conteúdo aceitos na resposta'
                    )
                ]
            )->withLabel('Cabeçalhos HTTP'),

            // Parâmetro opcional: Corpo da requisição
            ServiceParameter::object(
                name: 'body',
                defaultValue: [],
                description: 'Corpo da requisição para métodos POST/PUT/PATCH'
            )->withLabel('Corpo da Requisição'),
        ]);
    }
}