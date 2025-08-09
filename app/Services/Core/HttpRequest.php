<?php

namespace App\Services\Core;

use App\Contracts\Services\HttpRequestInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\ResponseInterface;

/**
 * Classe base para padronizar requisições HTTP
 */
abstract class HttpRequest implements HttpRequestInterface
{
    protected Client $client;
    protected string $baseUrl = '';
    protected string $method = 'GET';
    protected string $endpoint = '';
    protected array $headers = [];
    protected array $params = [];
    protected array $body = [];
    protected int $timeout = 30;
    protected int $connectTimeout = 10;
    protected bool $verifySSL = true;

    // Configurações específicas para arquivos
    protected bool $isFileUpload = false;
    protected array $files = [];
    protected bool $streamResponse = false;

    public function __construct(string $baseUrl = '', array $config = [])
    {
        $this->baseUrl = rtrim($baseUrl, '/');

        $this->initializeClient($config);
        $this->setDefaultHeaders();
    }

    /**
     * Inicializa o cliente HTTP com configurações personalizadas
     */
    protected function initializeClient(array $config = []): void
    {
        $defaultConfig = [
            'base_uri'        => $this->baseUrl,
            'verify'          => $this->verifySSL,
            'timeout'         => $this->timeout,
            'connect_timeout' => $this->connectTimeout,
            'http_errors'     => false, // Não lança exceção para status de erro HTTP
        ];

        $this->client = new Client(array_merge($defaultConfig, $config));
    }

    /**
     * Define headers padrão - pode ser sobrescrito pelas classes filhas
     */
    protected function setDefaultHeaders(): void
    {
        $this->headers = [
            'Accept'       => '*/*',
            'Content-Type' => 'application/json',
        ];
    }

    /**
     * Configura o método HTTP
     */
    public function setMethod(string $method): static
    {
        $this->method = strtoupper($method);
        return $this;
    }

    /**
     * Configura o endpoint da requisição
     */
    public function setEndpoint(string $endpoint): static
    {
        $this->endpoint = ltrim($endpoint, '/');
        return $this;
    }

    /**
     * Adiciona ou substitui headers
     */
    public function setHeaders(array $headers): static
    {
        $this->headers = array_merge($this->headers, $headers);
        return $this;
    }

    /**
     * Adiciona um header específico
     */
    public function addHeader(string $key, string $value): static
    {
        $this->headers[$key] = $value;
        return $this;
    }

    /**
     * Remove um header
     */
    public function removeHeader(string $key): static
    {
        unset($this->headers[$key]);
        return $this;
    }

    /**
     * Configura parâmetros de query string
     */
    public function setParams(array $params): static
    {
        $this->params = $params;
        return $this;
    }

    /**
     * Adiciona um parâmetro específico
     */
    public function addParam(string $key, mixed $value): static
    {
        $this->params[$key] = $value;
        return $this;
    }

    /**
     * Configura o corpo da requisição
     */
    public function setBody(array|string $body): static
    {
        $this->body = $body;
        return $this;
    }

    /**
     * Configura timeout da requisição
     */
    public function setTimeout(int $seconds): static
    {
        $this->timeout = $seconds;
        return $this;
    }

    /**
     * Configura timeout de conexão
     */
    public function setConnectTimeout(int $seconds): static
    {
        $this->connectTimeout = $seconds;
        return $this;
    }

    /**
     * Habilita/desabilita verificação SSL
     */
    public function setVerifySSL(bool $verify): static
    {
        $this->verifySSL = $verify;
        return $this;
    }

    /**
     * Configura upload de arquivo
     */
    public function setFileUpload(array $files): static
    {
        $this->isFileUpload = true;
        $this->files = $files;

        // Remove Content-Type para multipart/form-data ser definido automaticamente
        $this->removeHeader('Content-Type');

        return $this;
    }

    /**
     * Habilita streaming da resposta (útil para arquivos grandes)
     */
    public function enableStreaming(): static
    {
        $this->streamResponse = true;
        return $this;
    }

    /**
     * Executa a requisição HTTP
     */
    public function execute(): ApiResponse
    {
        try {
            // Aplica autenticação específica antes de construir as opções
            $this->applyAuthentication();

            $options = $this->buildRequestOptions();
            $url = $this->generateUrl();

            $response = $this->client->request($this->method, $url, $options);

            return $this->handleResponse($response);
        } catch (RequestException $e) {
            return $this->handleRequestException($e);
        } catch (\Exception|GuzzleException $e) {
            return $this->handleGenericException($e);
        }
    }

    /**
     * Constrói as opções da requisição
     */
    protected function buildRequestOptions(): array
    {
        $options = [
            'headers'         => $this->headers,
            'timeout'         => $this->timeout,
            'connect_timeout' => $this->connectTimeout,
            'verify'          => $this->verifySSL,
        ];

        // Streaming response
        if ($this->streamResponse) {
            $options['stream'] = true;
        }

        // Upload de arquivos
        if ($this->isFileUpload) {
            $options['multipart'] = $this->buildMultipartData();
        } elseif (in_array($this->method, ['POST', 'PUT', 'PATCH'])) {
            // Corpo da requisição para POST, PUT, PATCH
            if (!empty($this->body)) {
                if (isset($this->headers['Content-Type']) &&
                    str_contains($this->headers['Content-Type'], 'application/json')) {
                    $options['json'] = $this->body;
                } else {
                    $options['body'] = is_string($this->body) ? $this->body : json_encode($this->body);
                }
            }
        }

        // Query parameters sempre são adicionados na URL, não nas opções
        // quando há parâmetros, eles já são incluídos no generateUrl()

        return $options;
    }

    /**
     * Constrói dados multipart para upload de arquivos
     */
    protected function buildMultipartData(): array
    {
        $multipart = [];

        // Adiciona campos regulares
        if (!empty($this->body)) {
            foreach ($this->body as $key => $value) {
                $multipart[] = [
                    'name'     => $key,
                    'contents' => is_array($value) ? json_encode($value) : (string)$value,
                ];
            }
        }

        // Adiciona arquivos
        foreach ($this->files as $key => $file) {
            if (is_string($file)) {
                // Caminho do arquivo
                $multipart[] = [
                    'name'     => $key,
                    'contents' => fopen($file, 'r'),
                    'filename' => basename($file),
                ];
            } elseif (is_array($file) && isset($file['content'], $file['filename'])) {
                // Conteúdo direto do arquivo
                $multipart[] = [
                    'name'     => $key,
                    'contents' => $file['content'],
                    'filename' => $file['filename'],
                ];
            }
        }

        return $multipart;
    }

    /**
     * Processa a resposta HTTP
     */
    protected function handleResponse(ResponseInterface $response): ApiResponse
    {
        $statusCode = $response->getStatusCode();
        // Headers da resposta
        $headers = $response->getHeaders();

        // Se for streaming, retorna o stream
        if ($this->streamResponse) {
            return ApiResponse::success([
                'stream'      => $response->getBody(),
                'status_code' => $statusCode,
            ], 'Resposta recebida com sucesso');
        }

        $content = $response->getBody()->getContents();

        // Tenta decodificar JSON
        $data = $this->parseResponseContent($content, $headers);

        // Verifica se foi bem-sucedida
        if ($statusCode >= 200 && $statusCode < 300) {
            return ApiResponse::success($data, 'Requisição executada com sucesso', [
                'status_code' => $statusCode,
            ]);
        } else {

            return ApiResponse::error(
                "Erro HTTP {$statusCode}",
                $this->extractErrorsFromResponse($data, $statusCode),
                [
                    'status_code'  => $statusCode,
                    'raw_response' => $content
                ]
            );
        }
    }

    /**
     * Faz parsing do conteúdo da resposta
     */
    protected function parseResponseContent(string $content, array $headers): mixed
    {
        // Verifica se é JSON pelo Content-Type
        $contentType = $headers['Content-Type'][0] ?? $headers['content-type'][0] ?? '';

        if (str_contains($contentType, 'application/json') || $this->isJsonString($content)) {
            $decoded = json_decode($content, true);
            return $decoded !== null ? $decoded : $content;
        }

        return $content;
    }

    /**
     * Verifica se uma string é JSON válido
     */
    protected function isJsonString(string $string): bool
    {
        if (empty($string)) {
            return false;
        }

        json_decode($string);
        return json_last_error() === JSON_ERROR_NONE;
    }

    /**
     * Extrai erros da resposta baseado no formato
     */
    protected function extractErrorsFromResponse(mixed $data, int $statusCode): array
    {
        $errors = [];

        if (is_array($data)) {
            // Formatos comuns de erro em APIs
            if (isset($data['errors']) && is_array($data['errors'])) {
                $errors = $data['errors'];
            } elseif (isset($data['error'])) {
                $errors[] = $data['error'];
            } elseif (isset($data['message'])) {
                $errors[] = $data['message'];
            }
        } elseif (is_string($data)) {
            $errors[] = $data;
        }

        if (empty($errors)) {
            $errors[] = "Erro HTTP {$statusCode}";
        }

        return $errors;
    }

    /**
     * Trata exceções de requisição HTTP
     */
    protected function handleRequestException(RequestException $e): ApiResponse
    {
        $statusCode = $e->getResponse() ? $e->getResponse()->getStatusCode() : 0;
        $content = $e->getResponse() ? $e->getResponse()->getBody()->getContents() : '';

        return ApiResponse::error(
            'Erro na requisição HTTP: ' . $e->getMessage(),
            [$e->getMessage()],
            [
                'status_code'    => $statusCode,
                'raw_response'   => $content,
                'exception_type' => get_class($e),
            ]
        );
    }

    /**
     * Trata exceções genéricas
     */
    protected function handleGenericException(\Exception $e): ApiResponse
    {
        return ApiResponse::error(
            'Erro interno na requisição: ' . $e->getMessage(),
            [$e->getMessage()],
            [
                'exception_type' => get_class($e),
                'file'           => $e->getFile(),
                'line'           => $e->getLine(),
            ]
        );
    }

    /**
     * Método abstrato para aplicar autenticação específica
     * Deve ser implementado pelas classes filhas
     */
    abstract protected function applyAuthentication(): void;

    /**
     * Método para resetar a requisição para reutilização
     */
    public function reset(): static
    {
        $this->method = 'GET';
        $this->endpoint = '';
        $this->params = [];
        $this->body = [];
        $this->isFileUpload = false;
        $this->files = [];
        $this->streamResponse = false;

        $this->setDefaultHeaders();

        return $this;
    }

    /**
     * Gera a URL completa com query parameters
     */
    private function generateUrl(): string
    {
        $url = $this->endpoint;

        if (!empty($this->params)) {
            $url .= '?' . http_build_query($this->params);
        }

        return $url;
    }
}