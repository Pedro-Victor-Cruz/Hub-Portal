<?php

namespace App\Services\Erp\Drivers\Sankhya;

use App\Contracts\Erp\ErpAuthInterface;
use App\Exceptions\Erp\ErpAuthenticationException;
use App\Models\CompanyErpSetting;
use App\Services\Core\HttpRequest;
use App\Services\Core\ApiResponse;
use Psr\Http\Message\ResponseInterface;

/**
 * Classe específica para requisições HTTP ao Sankhya
 */
class SankhyaHttpRequest extends HttpRequest
{
    protected CompanyErpSetting $settings;
    protected ?ErpAuthInterface $authHandler = null;
    protected string $endpoint = 'mge/service.sbr';
    private const OUTPUT_FORMAT = 'json';

    public function __construct(CompanyErpSetting $settings, ?ErpAuthInterface $authHandler = null)
    {
        $this->settings = $settings;
        $this->authHandler = $authHandler;

        parent::__construct($this->getBaseUrl(), [
            'verify' => config('app.env') === 'production', // SSL apenas em produção
        ]);
    }

    private function getBaseUrl(): string
    {
        if ($this->authHandler && $this->authHandler->getAuthType() === 'mobile_login') {
            return $this->settings->base_url;
        } else {
            return 'https://api.sankhya.com.br/gateway/v1';
        }
    }

    /**
     * Configura headers específicos do Sankhya
     */
    protected function setDefaultHeaders(): void
    {
        parent::setDefaultHeaders();

        // Headers específicos do Sankhya
        $this->headers = array_merge($this->headers, [
            'Accept' => 'application/json, text/plain, */*',
            'Accept-Language' => 'pt-BR,pt;q=0.9,en;q=0.8',
            'Content-Type' => 'application/json;charset=UTF-8',
        ]);
    }

    /**
     * Aplica autenticação específica do Sankhya
     */
    protected function applyAuthentication(): void
    {
        if (!$this->authHandler) {
            return;
        }

        try {
            $authType = $this->authHandler->getAuthType();
            $token = $this->authHandler->getToken();

            if (!$token) {
                return;
            }

            // Adiciona o token como parâmetro da query string
            $this->addParam('mgeSession', $token);

            switch ($authType) {
                case 'mobile_login':
                    $this->applySankhyaSessionAuth($token);
                    break;

                case 'token_auth':
                    $this->applySankhyaTokenAuth($token);
                    break;

                default:
                    // Tenta autenticação genérica como fallback
                    $this->addHeader('Authorization', "Bearer {$token}");
                    break;
            }
        } catch (\Exception $e) {
            // Log do erro se necessário
        }
    }

    /**
     * Aplica autenticação por sessão do Sankhya
     */
    protected function applySankhyaSessionAuth(string $sessionId): void
    {
        $jsessionId = $sessionId . '.master';

        $this->addHeader('JSESSIONID', $jsessionId);
        $this->addHeader('Cookie', 'JSESSIONID=' . $jsessionId);
    }

    /**
     * Aplica autenticação por token do Sankhya
     */
    protected function applySankhyaTokenAuth(string $token): void
    {
        $this->addHeader('Authorization', "Bearer {$token}");
        $this->addHeader('Token', $token);
    }

    /**
     * Configura requisição para service específico do Sankhya
     */
    public function setService(string $serviceName, array $requestBody = []): static
    {
        $this->setMethod('POST')
            ->setEndpoint($this->endpoint)
            ->addParam('serviceName', $serviceName)
            ->addParam('outputType', self::OUTPUT_FORMAT)
            ->setBody([
                'serviceName' => $serviceName,
                'requestBody' => $requestBody
            ]);

        return $this;
    }

    /**
     * Processa resposta específica do Sankhya
     */
    protected function handleResponse(ResponseInterface $response): ApiResponse
    {
        $apiResponse = parent::handleResponse($response);

        // Se a requisição foi bem-sucedida, verifica estrutura específica do Sankhya
        if ($apiResponse->isSuccess()) {
            $data = $apiResponse->getData();

            // Debug: adiciona a resposta raw para análise
            $debugInfo = [
                'url' => $this->baseUrl . '/' . $this->generateUrlForDebug(),
                'method' => $this->method,
                'headers' => $this->headers,
                'params' => $this->params,
                'body' => $this->body,
                'raw_response' => $data, // Adiciona a resposta completa
            ];

            // Verifica se há erro na estrutura de resposta do Sankhya
            if (is_array($data) && isset($data['status'])) {
                $status = $data['status'];

                // Converte status para comparação (pode vir como string ou int)
                $statusInt = is_string($status) ? (int)$status : $status;

                if ($statusInt !== 1) {
                    $errorMessage = $data['statusMessage'] ?? 'Erro desconhecido no Sankhya';
                    $errors = [];

                    // Extrai erros mais detalhados se existirem
                    if (isset($data['responseBody']['errors'])) {
                        $errors = is_array($data['responseBody']['errors'])
                            ? $data['responseBody']['errors']
                            : [$data['responseBody']['errors']];
                    }

                    if (empty($errors)) {
                        $errors[] = $errorMessage;
                    }

                    return ApiResponse::error(
                        "Erro do Sankhya: {$errorMessage}",
                        $errors,
                        array_merge($apiResponse->getMetadata(), [
                            'sankhya_status' => $status,
                            'sankhya_message' => $errorMessage,
                            'debug_info' => $debugInfo
                        ])
                    );
                }

                // Status 1 = sucesso, verifica se tem responseBody
                if (isset($data['responseBody'])) {
                    return ApiResponse::success(
                        $data['responseBody'],
                        'Requisição Sankhya executada com sucesso',
                        array_merge($apiResponse->getMetadata(), [
                            'sankhya_status' => $status,
                            'sankhya_message' => $data['statusMessage'] ?? 'Sucesso',
                        ])
                    );
                } else {
                    // Sucesso mas sem responseBody, retorna os dados como estão
                    return ApiResponse::success(
                        $data,
                        'Requisição Sankhya executada com sucesso',
                        array_merge($apiResponse->getMetadata(), [
                            'sankhya_status' => $status,
                            'sankhya_message' => $data['statusMessage'] ?? 'Sucesso',
                        ])
                    );
                }
            } else {
                // Resposta sem estrutura padrão do Sankhya
                return ApiResponse::success(
                    $data,
                    'Resposta recebida (estrutura não padrão)',
                    array_merge($apiResponse->getMetadata(), [
                        'debug_info' => $debugInfo,
                        'note' => 'Resposta não segue estrutura padrão do Sankhya'
                    ])
                );
            }
        }

        return $apiResponse;
    }

    /**
     * Extrai erros específicos do formato Sankhya
     */
    protected function extractErrorsFromResponse(mixed $data, int $statusCode): array
    {
        $errors = parent::extractErrorsFromResponse($data, $statusCode);

        // Formatos específicos de erro do Sankhya
        if (is_array($data)) {
            if (isset($data['statusMessage'])) {
                $errors[] = $data['statusMessage'];
            }

            if (isset($data['responseBody']['errors'])) {
                $sankhyaErrors = is_array($data['responseBody']['errors'])
                    ? $data['responseBody']['errors']
                    : [$data['responseBody']['errors']];

                $errors = array_merge($errors, $sankhyaErrors);
            }

            // Remove duplicatas
            $errors = array_unique($errors);
        }

        return $errors;
    }

    /**
     * Método específico para chamar serviços Sankhya
     */
    public function callService(string $serviceName, array $requestBody = []): ApiResponse
    {
        return $this->setService($serviceName, $requestBody)->execute();
    }

    /**
     * Método auxiliar para debug da URL
     */
    private function generateUrlForDebug(): string
    {
        $url = $this->endpoint;

        if (!empty($this->params)) {
            $url .= '?' . http_build_query($this->params);
        }

        return $url;
    }
}