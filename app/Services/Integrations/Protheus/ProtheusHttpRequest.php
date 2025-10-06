<?php

namespace App\Services\Integrations\Protheus;

use App\Contracts\Auth\AuthHandlerInterface;
use App\Services\Core\ApiResponse;
use App\Services\Core\HttpRequest;
use App\Services\Core\Integration\BaseIntegration;
use Psr\Http\Message\ResponseInterface;

/**
 * Classe específica para requisições HTTP ao TOTVS Protheus
 */
class ProtheusHttpRequest extends HttpRequest
{
    protected BaseIntegration $integration;
    protected ?AuthHandlerInterface $authHandler = null;

    public function __construct(BaseIntegration $integration)
    {
        $this->integration = $integration;

        parent::__construct($this->getBaseUrl(), [
            'verify' => $this->integration->getConfig('use_ssl_verification', true),
            'timeout' => $this->integration->getConfig('timeout', 30),
        ]);
    }

    private function getBaseUrl(): string
    {
        return $this->integration->getConfig('base_url');
    }

    /**
     * Configura headers específicos do Protheus
     */
    protected function setDefaultHeaders(): void
    {
        parent::setDefaultHeaders();

        // Headers específicos do Protheus
        $this->headers = array_merge($this->headers, [
            'Accept' => 'application/json',
            'Accept-Language' => 'pt-BR',
            'Content-Type' => 'application/json;charset=UTF-8',
        ]);
    }

    /**
     * Aplica autenticação específica do Protheus
     */
    protected function applyAuthentication(): void
    {
        try {
            $authType = $this->integration->getConfig('auth_type', 'oauth');

            $this->authHandler = match ($authType) {
                'oauth' => new Auth\ProtheusOAuthHandler($this->integration),
                'basic' => new Auth\ProtheusBasicAuthHandler($this->integration),
                default => null,
            };

            if (!$this->authHandler) {
                return;
            }

            $token = $this->authHandler->getAuthToken();

            if (!$token) {
                return;
            }

            switch ($authType) {
                case 'oauth':
                    $this->applyOAuthAuthentication($token);
                    break;
                case 'basic':
                    $this->applyBasicAuthentication($token);
                    break;
                default:
                    $this->addHeader('Authorization', "Bearer {$token}");
                    break;
            }
        } catch (\Exception $e) {
            // Log do erro se necessário
        }
    }

    /**
     * Aplica autenticação OAuth
     */
    protected function applyOAuthAuthentication(string $token): void
    {
        $this->addHeader('Authorization', "Bearer {$token}");
    }

    /**
     * Aplica autenticação Basic
     */
    protected function applyBasicAuthentication(string $token): void
    {
        $this->addHeader('Authorization', "Basic {$token}");
    }

    /**
     * Adiciona empresa e filial aos headers (padrão Protheus)
     */
    public function withCompanyBranch(): static
    {
        $company = $this->integration->getConfig('company', '01');
        $branch = $this->integration->getConfig('branch', '0101');

        $this->addHeader('companyId', $company);
        $this->addHeader('branchId', $branch);

        return $this;
    }

    /**
     * Processa resposta específica do Protheus
     */
    protected function handleResponse(ResponseInterface $response): ApiResponse
    {
        $apiResponse = parent::handleResponse($response);

        if ($apiResponse->isSuccess()) {
            $data = $apiResponse->getData();

            // Debug info
            $debugInfo = [
                'url' => $this->baseUrl . '/' . $this->generateUrlForDebug(),
                'method' => $this->method,
                'headers' => $this->headers,
                'params' => $this->params,
                'body' => $this->body,
                'raw_response' => $data,
            ];

            // Verifica estruturas de erro comuns do Protheus
            if (is_array($data)) {
                // Formato de erro: {"errorMessage": "...", "detailedMessage": "..."}
                if (isset($data['errorMessage']) || isset($data['error'])) {
                    $errorMessage = $data['errorMessage'] ?? $data['error'] ?? 'Erro desconhecido no Protheus';
                    $detailedMessage = $data['detailedMessage'] ?? $data['error_description'] ?? '';

                    $errors = [$errorMessage];
                    if (!empty($detailedMessage) && $detailedMessage !== $errorMessage) {
                        $errors[] = $detailedMessage;
                    }

                    return ApiResponse::error(
                        "Erro do Protheus: {$errorMessage}",
                        $errors,
                        array_merge($apiResponse->getMetadata(), [
                            'protheus_error' => $errorMessage,
                            'protheus_detail' => $detailedMessage,
                            'debug_info' => $debugInfo
                        ])
                    );
                }

                // Formato de resposta com items (lista de registros)
                if (isset($data['items']) && is_array($data['items'])) {
                    return ApiResponse::success(
                        $data['items'],
                        'Requisição Protheus executada com sucesso',
                        array_merge($apiResponse->getMetadata(), [
                            'total_items' => count($data['items']),
                            'has_next' => $data['hasNext'] ?? false,
                        ])
                    );
                }

                // Formato de resposta única
                if (isset($data['item'])) {
                    return ApiResponse::success(
                        $data['item'],
                        'Requisição Protheus executada com sucesso',
                        $apiResponse->getMetadata()
                    );
                }

                // Retorna os dados como estão
                return ApiResponse::success(
                    $data,
                    'Requisição Protheus executada com sucesso',
                    $apiResponse->getMetadata()
                );
            }

            return $apiResponse;
        }

        return $apiResponse;
    }

    /**
     * Extrai erros específicos do formato Protheus
     */
    protected function extractErrorsFromResponse(mixed $data, int $statusCode): array
    {
        $errors = parent::extractErrorsFromResponse($data, $statusCode);

        if (is_array($data)) {
            // Formatos de erro do Protheus
            if (isset($data['errorMessage'])) {
                $errors[] = $data['errorMessage'];
            }

            if (isset($data['detailedMessage']) && $data['detailedMessage'] !== ($data['errorMessage'] ?? '')) {
                $errors[] = $data['detailedMessage'];
            }

            if (isset($data['error'])) {
                $errors[] = is_string($data['error']) ? $data['error'] : json_encode($data['error']);
            }

            if (isset($data['error_description'])) {
                $errors[] = $data['error_description'];
            }

            // Erros de validação
            if (isset($data['fields']) && is_array($data['fields'])) {
                foreach ($data['fields'] as $field) {
                    if (isset($field['message'])) {
                        $errors[] = $field['message'];
                    }
                }
            }

            $errors = array_unique(array_filter($errors));
        }

        return $errors;
    }

    /**
     * Método auxiliar para facilitar chamadas à API REST do Protheus
     */
    public function restRequest(
        string $method,
        string $endpoint,
        array $body = [],
        array $queryParams = []
    ): ApiResponse {
        $this->setMethod($method)
            ->setEndpoint($endpoint)
            ->withCompanyBranch();

        if (!empty($queryParams)) {
            foreach ($queryParams as $key => $value) {
                $this->addParam($key, $value);
            }
        }

        if (!empty($body) && in_array($method, ['POST', 'PUT', 'PATCH'])) {
            $this->setBody($body);
        }

        return $this->execute();
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

    /**
     * Adiciona filtros ao formato OData (usado pelo Protheus)
     */
    public function addODataFilter(string $filter): static
    {
        $this->addParam('$filter', $filter);
        return $this;
    }

    /**
     * Adiciona ordenação ao formato OData
     */
    public function addODataOrderBy(string $orderBy): static
    {
        $this->addParam('$orderby', $orderBy);
        return $this;
    }

    /**
     * Adiciona paginação ao formato OData
     */
    public function addODataPagination(int $top, int $skip = 0): static
    {
        $this->addParam('$top', $top);
        if ($skip > 0) {
            $this->addParam('$skip', $skip);
        }
        return $this;
    }

    /**
     * Adiciona seleção de campos ao formato OData
     */
    public function addODataSelect(array $fields): static
    {
        $this->addParam('$select', implode(',', $fields));
        return $this;
    }
}