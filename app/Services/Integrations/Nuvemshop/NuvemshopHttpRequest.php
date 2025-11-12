<?php

namespace App\Services\Integrations\Nuvemshop;

use App\Services\Core\ApiResponse;
use App\Services\Core\HttpRequest;
use App\Services\Core\Integration\BaseIntegration;
use App\Services\Integrations\Nuvemshop\Auth\NuvemshopOAuthHandler;
use Psr\Http\Message\ResponseInterface;

/**
 * Cliente HTTP específico para requisições à API da Nuvemshop
 *
 * Implementa rate limiting (leaky bucket), retry com backoff exponencial
 * e headers obrigatórios da API
 *
 * @see https://tiendanube.github.io/api-documentation/intro
 */
class NuvemshopHttpRequest extends HttpRequest
{
    protected BaseIntegration $integration;
    protected ?NuvemshopOAuthHandler $authHandler = null;

    // Rate limiting - Leaky Bucket Algorithm
    private static array $rateLimitBuckets = [];
    private const DEFAULT_MAX_REQUESTS = 40;
    private const DEFAULT_LEAK_RATE = 2; // req/s

    // Retry configuration
    private const MAX_RETRIES = 3;
    private const RETRY_DELAY_MS = 1000;
    private const RETRY_MULTIPLIER = 2;

    public function __construct(BaseIntegration $integration)
    {
        $this->integration = $integration;

        parent::__construct($this->getBaseUrl(), [
            'verify' => config('app.env') === 'production',
            'timeout' => 30,
            'connect_timeout' => 10,
        ]);
    }

    private function getBaseUrl(): string
    {
        return $this->integration->getBaseUrl();
    }

    /**
     * Configura headers obrigatórios da Nuvemshop
     */
    protected function setDefaultHeaders(): void
    {
        parent::setDefaultHeaders();

        $this->headers = array_merge($this->headers, [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'User-Agent' => $this->integration->getUserAgent(),
        ]);
    }

    /**
     * Aplica autenticação OAuth2 da Nuvemshop
     */
    protected function applyAuthentication(): void
    {
        try {
            $this->authHandler = new NuvemshopOAuthHandler($this->integration);
            $token = $this->authHandler->getAuthToken();

            if ($token) {
                $this->addHeader('Authentication', "bearer {$token}");
            }
        } catch (\Exception $e) {
            // Log se necessário, mas não interrompe a execução
            logger()->error('Nuvemshop auth error', [
                'message' => $e->getMessage(),
                'store_id' => $this->integration->getStoreId()
            ]);
        }
    }

    /**
     * Executa requisição com rate limiting e retry automático
     */
    public function execute(): ApiResponse
    {
        $attempt = 0;
        $lastException = null;

        while ($attempt < self::MAX_RETRIES) {
            try {
                // Aguarda rate limiting
                $this->waitForRateLimit();

                // Executa requisição
                $response = parent::execute();

                // Se for 429 (Too Many Requests), faz retry
                if ($response->getMetadata()['status_code'] ?? null === 429) {
                    $attempt++;
                    $this->handleRateLimitExceeded($attempt);
                    continue;
                }

                // Se for 5xx, faz retry
                $statusCode = $response->getMetadata()['status_code'] ?? 0;
                if ($statusCode >= 500 && $statusCode < 600) {
                    $attempt++;
                    $this->sleep($attempt);
                    continue;
                }

                return $response;

            } catch (\Exception $e) {
                $lastException = $e;
                $attempt++;

                if ($attempt >= self::MAX_RETRIES) {
                    break;
                }

                $this->sleep($attempt);
            }
        }

        // Se chegou aqui, todas as tentativas falharam
        return ApiResponse::error(
            'Falha após múltiplas tentativas',
            [$lastException?->getMessage() ?? 'Erro desconhecido'],
            [
                'attempts' => $attempt,
                'last_error' => $lastException?->getMessage()
            ]
        );
    }

    /**
     * Implementa Leaky Bucket Algorithm para rate limiting
     */
    private function waitForRateLimit(): void
    {
        $storeId = $this->integration->getStoreId();
        $config = $this->integration->getRateLimitConfig();

        $maxRequests = $config['requests'];
        $leakSeconds = $config['seconds'];
        $leakRate = $maxRequests / $leakSeconds; // req/s

        if (!isset(self::$rateLimitBuckets[$storeId])) {
            self::$rateLimitBuckets[$storeId] = [
                'tokens' => $maxRequests,
                'last_update' => microtime(true),
            ];
        }

        $bucket = &self::$rateLimitBuckets[$storeId];
        $now = microtime(true);
        $elapsed = $now - $bucket['last_update'];

        // Adiciona tokens que "vazaram" desde a última verificação
        $bucket['tokens'] = min(
            $maxRequests,
            $bucket['tokens'] + ($elapsed * $leakRate)
        );
        $bucket['last_update'] = $now;

        // Se não há tokens disponíveis, aguarda
        if ($bucket['tokens'] < 1) {
            $waitTime = (1 - $bucket['tokens']) / $leakRate;
            usleep((int)($waitTime * 1000000));

            $bucket['tokens'] = 1;
        }

        // Consome um token
        $bucket['tokens'] -= 1;
    }

    /**
     * Trata erro 429 (Rate Limit Exceeded)
     */
    private function handleRateLimitExceeded(int $attempt): void
    {
        // Reseta o bucket para forçar espera
        $storeId = $this->integration->getStoreId();
        if (isset(self::$rateLimitBuckets[$storeId])) {
            self::$rateLimitBuckets[$storeId]['tokens'] = 0;
        }

        // Aguarda com backoff exponencial
        $this->sleep($attempt, 2000); // Base de 2 segundos para 429
    }

    /**
     * Sleep com backoff exponencial
     */
    private function sleep(int $attempt, int $baseMs = self::RETRY_DELAY_MS): void
    {
        $delay = $baseMs * pow(self::RETRY_MULTIPLIER, $attempt - 1);
        usleep($delay * 1000);
    }

    /**
     * Processa resposta específica da Nuvemshop
     */
    protected function handleResponse(ResponseInterface $response): ApiResponse
    {
        $apiResponse = parent::handleResponse($response);
        $statusCode = $response->getStatusCode();

        // Headers importantes da Nuvemshop
        $rateLimitHeaders = [
            'limit' => $response->getHeader('X-Rate-Limit-Limit')[0] ?? null,
            'remaining' => $response->getHeader('X-Rate-Limit-Remaining')[0] ?? null,
            'reset' => $response->getHeader('X-Rate-Limit-Reset')[0] ?? null,
        ];

        $metadata = array_merge($apiResponse->getMetadata(), [
            'rate_limit' => $rateLimitHeaders,
            'store_id' => $this->integration->getStoreId(),
        ]);

        // Sucesso (2xx)
        if ($statusCode >= 200 && $statusCode < 300) {
            return ApiResponse::success(
                $apiResponse->getData(),
                'Requisição executada com sucesso',
                $metadata
            );
        }

        // Erros específicos da Nuvemshop
        return $this->handleNuvemshopError($apiResponse, $statusCode, $metadata);
    }

    /**
     * Trata erros específicos da API Nuvemshop
     */
    private function handleNuvemshopError(
        ApiResponse $apiResponse,
        int $statusCode,
        array $metadata
    ): ApiResponse {
        $data = $apiResponse->getData();
        $errors = [];
        $message = 'Erro na requisição à Nuvemshop';

        // Formatos de erro da Nuvemshop
        if (is_array($data)) {
            // Formato: {"code": "invalid", "message": "...", "description": "..."}
            if (isset($data['code'])) {
                $errors[] = sprintf(
                    '[%s] %s',
                    $data['code'],
                    $data['message'] ?? $data['description'] ?? 'Erro desconhecido'
                );
            }

            // Formato: [{"code": "...", "message": "...", "field": "..."}]
            if (isset($data[0]['code'])) {
                foreach ($data as $error) {
                    $field = isset($error['field']) ? " ({$error['field']})" : '';
                    $errors[] = sprintf(
                        '[%s]%s %s',
                        $error['code'],
                        $field,
                        $error['message'] ?? $error['description'] ?? ''
                    );
                }
            }

            // Erro simples
            if (isset($data['error'])) {
                $errors[] = is_string($data['error'])
                    ? $data['error']
                    : json_encode($data['error']);
            }
        }

        // Se não extraiu nenhum erro específico, usa mensagem padrão
        if (empty($errors)) {
            $errors[] = match ($statusCode) {
                400 => 'Requisição inválida (Bad Request)',
                401 => 'Não autenticado - Token inválido ou expirado',
                403 => 'Sem permissão para acessar este recurso',
                404 => 'Recurso não encontrado',
                422 => 'Entidade não processável - Dados inválidos',
                429 => 'Limite de requisições excedido (Rate Limit)',
                500 => 'Erro interno do servidor Nuvemshop',
                503 => 'Serviço temporariamente indisponível',
                default => "Erro HTTP {$statusCode}",
            };
        }

        return ApiResponse::error($message, $errors, $metadata);
    }

    /**
     * Métodos de conveniência para operações REST
     */
    public function get(string $endpoint, array $params = []): ApiResponse
    {
        return $this->setMethod('GET')
            ->setEndpoint($endpoint)
            ->setParams($params)
            ->execute();
    }

    public function post(string $endpoint, array $data = []): ApiResponse
    {
        return $this->setMethod('POST')
            ->setEndpoint($endpoint)
            ->setBody($data)
            ->execute();
    }

    public function put(string $endpoint, array $data = []): ApiResponse
    {
        return $this->setMethod('PUT')
            ->setEndpoint($endpoint)
            ->setBody($data)
            ->execute();
    }

    public function delete(string $endpoint): ApiResponse
    {
        return $this->setMethod('DELETE')
            ->setEndpoint($endpoint)
            ->execute();
    }

    /**
     * Busca recursos com paginação automática
     */
    public function paginate(
        string $endpoint,
        array $params = [],
        ?int $maxPages = null
    ): ApiResponse {
        $allData = [];
        $page = $params['page'] ?? 1;
        $perPage = $params['per_page'] ?? 50; // Máximo da API é 200
        $collectedPages = 0;

        do {
            $response = $this->get($endpoint, array_merge($params, [
                'page' => $page,
                'per_page' => $perPage,
            ]));

            if (!$response->isSuccess()) {
                // Se falhou, retorna o que já coletou (se houver)
                if (!empty($allData)) {
                    return ApiResponse::success($allData,
                        'Paginação parcial - erro na página ' . $page,
                        [
                            'total_collected' => count($allData),
                            'last_successful_page' => $page - 1,
                            'error' => $response->getErrors(),
                        ]
                    );
                }
                return $response;
            }

            $data = $response->getData();

            // Se não retornou array ou está vazio, termina
            if (!is_array($data) || empty($data)) {
                break;
            }

            $allData = array_merge($allData, $data);
            $page++;
            $collectedPages++;

            // Se tem menos itens que o solicitado, é a última página
            if (count($data) < $perPage) {
                break;
            }

            // Se atingiu o limite de páginas
            if ($maxPages !== null && $collectedPages >= $maxPages) {
                break;
            }

        } while (true);

        return ApiResponse::success($allData, 'Dados coletados com sucesso', [
            'total_items' => count($allData),
            'pages_collected' => $collectedPages,
        ]);
    }
}