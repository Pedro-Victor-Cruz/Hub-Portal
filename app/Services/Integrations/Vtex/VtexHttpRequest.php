<?php

namespace App\Services\Integrations\Vtex;

use App\Contracts\Auth\AuthHandlerInterface;
use App\Services\Core\ApiResponse;
use App\Services\Core\HttpRequest;
use App\Services\Core\Integration\BaseIntegration;
use App\Services\Integrations\Vtex\Auth\VtexAppKeyAuthHandler;
use App\Services\Integrations\Vtex\Auth\VtexUserTokenAuthHandler;
use Psr\Http\Message\ResponseInterface;

/**
 * Classe específica para requisições HTTP à VTEX
 */
class VtexHttpRequest extends HttpRequest
{
    protected BaseIntegration $integration;
    protected ?AuthHandlerInterface $authHandler = null;
    protected string $apiContext = '';

    // Rate limiting
    private int $retryCount = 0;
    private int $maxRetries = 3;

    // Contextos de API disponíveis na VTEX
    private const API_CONTEXTS = [
        'catalog' => 'api/catalog',
        'oms' => 'api/oms',
        'pricing' => 'api/pricing',
        'logistics' => 'api/logistics',
        'checkout' => 'api/checkout',
        'masterdata' => 'api/dataentities',
        'payments' => 'api/pvt/payments',
        'customer-credit' => 'api/customer-credit',
        'giftcard' => 'api/giftcard',
        'promotions' => 'api/rnb',
        'search' => 'api/io',
        'session' => 'api/sessions',
        'license-manager' => 'api/license-manager',
        'vtex-id' => 'api/vtexid',
    ];

    public function __construct(BaseIntegration $integration)
    {
        $this->integration = $integration;
        $this->maxRetries = $integration->getConfig('retry_attempts', 3);

        parent::__construct($this->getBaseUrl(), [
            'verify' => config('app.env') === 'production',
            'timeout' => $integration->getConfig('timeout', 30),
        ]);
    }

    private function getBaseUrl(): string
    {
        return $this->integration->getBaseUrl();
    }

    /**
     * Configura headers específicos da VTEX
     */
    protected function setDefaultHeaders(): void
    {
        parent::setDefaultHeaders();

        $this->headers = array_merge($this->headers, [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'Accept-Encoding' => 'gzip, deflate, br',
        ]);
    }

    /**
     * Aplica autenticação específica da VTEX
     */
    protected function applyAuthentication(): void
    {
        try {
            $authType = $this->integration->getConfig('auth_type', 'app_key');

            $this->authHandler = match ($authType) {
                'app_key' => new VtexAppKeyAuthHandler($this->integration),
                'user_token' => new VtexUserTokenAuthHandler($this->integration),
                default => null,
            };

            if (!$this->authHandler) {
                return;
            }

            $token = $this->authHandler->getAuthToken();

            if (!$token) {
                return;
            }

            // Aplica autenticação baseada no tipo
            switch ($authType) {
                case 'app_key':
                    $this->applyAppKeyAuth($token);
                    break;
                case 'user_token':
                    $this->applyUserTokenAuth($token);
                    break;
            }
        } catch (\Exception $e) {
            // Log do erro se necessário
            logger()->error('VTEX Auth Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Aplica autenticação por App Key e App Token
     */
    protected function applyAppKeyAuth(array $credentials): void
    {
        $this->addHeader('X-VTEX-API-AppKey', $credentials['app_key']);
        $this->addHeader('X-VTEX-API-AppToken', $credentials['app_token']);
    }

    /**
     * Aplica autenticação por User Token (Cookie)
     */
    protected function applyUserTokenAuth(string $token): void
    {
        $this->addHeader('VtexIdclientAutCookie', $token);
        $this->addHeader('Cookie', "VtexIdclientAutCookie={$token}");
    }

    /**
     * Define o contexto da API (catalog, oms, pricing, etc)
     */
    public function setApiContext(string $context): static
    {
        if (!isset(self::API_CONTEXTS[$context])) {
            throw new \InvalidArgumentException("Contexto de API inválido: {$context}");
        }

        $this->apiContext = self::API_CONTEXTS[$context];
        return $this;
    }

    /**
     * Sobrescreve o método setEndpoint para incluir o contexto da API
     */
    public function setEndpoint(string $endpoint): static
    {
        // Remove barras iniciais
        $endpoint = ltrim($endpoint, '/');

        // Se há um contexto definido e o endpoint não começa com 'api/'
        if ($this->apiContext && !str_starts_with($endpoint, 'api/')) {
            $endpoint = $this->apiContext . '/' . $endpoint;
        }

        return parent::setEndpoint($endpoint);
    }

    /**
     * Processa resposta específica da VTEX
     */
    protected function handleResponse(ResponseInterface $response): ApiResponse
    {
        $statusCode = $response->getStatusCode();

        // Verifica rate limiting (429 Too Many Requests)
        if ($statusCode === 429 && $this->retryCount < $this->maxRetries) {
            return $this->handleRateLimiting($response);
        }

        $apiResponse = parent::handleResponse($response);

        // Debug info
        $debugInfo = [
            'url' => $this->baseUrl . '/' . $this->generateUrlForDebug(),
            'method' => $this->method,
            'headers' => $this->sanitizeHeadersForDebug(),
            'params' => $this->params,
            'body' => $this->body,
            'status_code' => $statusCode,
        ];

        // Se a requisição foi bem-sucedida
        if ($apiResponse->isSuccess()) {
            $data = $apiResponse->getData();

            // Adiciona informações de paginação se existirem
            $paginationInfo = $this->extractPaginationInfo($response);

            return ApiResponse::success(
                $data,
                'Requisição VTEX executada com sucesso',
                array_merge($apiResponse->getMetadata(), [
                    'vtex_request_id' => $response->getHeaderLine('X-VTEX-Operation-Id'),
                    'pagination' => $paginationInfo,
                    'debug_info' => $debugInfo,
                ])
            );
        }

        // Trata erros específicos da VTEX
        if (!$apiResponse->isSuccess()) {
            $data = $apiResponse->getData();
            $errorMessage = $this->extractVtexErrorMessage($data, $statusCode);

            return ApiResponse::error(
                $errorMessage,
                $apiResponse->getErrors(),
                array_merge($apiResponse->getMetadata(), [
                    'vtex_request_id' => $response->getHeaderLine('X-VTEX-Operation-Id'),
                    'debug_info' => $debugInfo,
                ])
            );
        }

        return $apiResponse;
    }

    /**
     * Trata rate limiting com retry exponencial
     */
    protected function handleRateLimiting(ResponseInterface $response): ApiResponse
    {
        $this->retryCount++;

        // Extrai o tempo de espera do header Retry-After
        $retryAfter = (int) $response->getHeaderLine('Retry-After') ?: (2 ** $this->retryCount);

        logger()->warning('VTEX Rate Limit', [
            'retry_count' => $this->retryCount,
            'retry_after' => $retryAfter,
            'url' => $this->generateUrlForDebug()
        ]);

        // Aguarda antes de tentar novamente
        sleep($retryAfter);

        // Executa novamente a requisição
        return $this->execute();
    }

    /**
     * Extrai mensagem de erro específica da VTEX
     */
    protected function extractVtexErrorMessage(mixed $data, int $statusCode): string
    {
        // Formato padrão de erro da VTEX
        if (is_array($data)) {
            if (isset($data['error']['message'])) {
                return "Erro VTEX: {$data['error']['message']}";
            }

            if (isset($data['message'])) {
                return "Erro VTEX: {$data['message']}";
            }

            if (isset($data['Message'])) {
                return "Erro VTEX: {$data['Message']}";
            }
        }

        // Mensagens padrão por código HTTP
        return match ($statusCode) {
            400 => 'Requisição inválida',
            401 => 'Não autorizado - Verifique suas credenciais',
            403 => 'Acesso negado - Permissões insuficientes',
            404 => 'Recurso não encontrado',
            429 => 'Muitas requisições - Rate limit excedido',
            500 => 'Erro interno do servidor VTEX',
            503 => 'Serviço VTEX temporariamente indisponível',
            default => "Erro HTTP {$statusCode}",
        };
    }

    /**
     * Extrai informações de paginação dos headers
     */
    protected function extractPaginationInfo(ResponseInterface $response): ?array
    {
        $resourcesHeader = $response->getHeaderLine('REST-Content-Range');

        if (!$resourcesHeader) {
            return null;
        }

        // Formato: resources 0-14/100
        if (preg_match('/resources (\d+)-(\d+)\/(\d+)/', $resourcesHeader, $matches)) {
            return [
                'from' => (int) $matches[1],
                'to' => (int) $matches[2],
                'total' => (int) $matches[3],
                'has_more' => (int) $matches[2] < (int) $matches[3],
            ];
        }

        return null;
    }

    /**
     * Extrai erros específicos da VTEX
     */
    protected function extractErrorsFromResponse(mixed $data, int $statusCode): array
    {
        $errors = parent::extractErrorsFromResponse($data, $statusCode);

        if (is_array($data)) {
            // Formato padrão de erro da VTEX
            if (isset($data['error']['message'])) {
                $errors[] = $data['error']['message'];
            }

            if (isset($data['message'])) {
                $errors[] = $data['message'];
            }

            if (isset($data['Message'])) {
                $errors[] = $data['Message'];
            }

            // Erros de validação
            if (isset($data['errors']) && is_array($data['errors'])) {
                foreach ($data['errors'] as $error) {
                    if (is_string($error)) {
                        $errors[] = $error;
                    } elseif (is_array($error) && isset($error['message'])) {
                        $errors[] = $error['message'];
                    }
                }
            }

            // Remove duplicatas
            $errors = array_unique($errors);
        }

        return $errors;
    }

    /**
     * Sanitiza headers para debug (remove credenciais)
     */
    private function sanitizeHeadersForDebug(): array
    {
        $headers = $this->headers;

        $sensitiveHeaders = [
            'X-VTEX-API-AppKey',
            'X-VTEX-API-AppToken',
            'VtexIdclientAutCookie',
            'Authorization',
            'Cookie',
        ];

        foreach ($sensitiveHeaders as $header) {
            if (isset($headers[$header])) {
                $headers[$header] = '***REDACTED***';
            }
        }

        return $headers;
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
     * Métodos de conveniência para operações comuns da VTEX
     */

    /**
     * Busca com paginação automática
     */
    public function paginate(int $page = 1, int $perPage = 100): static
    {
        $from = ($page - 1) * $perPage;
        $to = $from + $perPage - 1;

        $this->addHeader('REST-Range', "resources={$from}-{$to}");

        return $this;
    }

    /**
     * Define o filtro para APIs que suportam
     */
    public function filter(string $filter): static
    {
        $this->addParam('_filter', $filter);
        return $this;
    }

    /**
     * Define os campos a serem retornados
     */
    public function fields(array $fields): static
    {
        $this->addParam('_fields', implode(',', $fields));
        return $this;
    }

    /**
     * Define ordenação
     */
    public function orderBy(string $field, string $direction = 'ASC'): static
    {
        $this->addParam('_sort', "{$field} {$direction}");
        return $this;
    }
}