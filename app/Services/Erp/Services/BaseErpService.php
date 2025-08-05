<?php

namespace App\Services\Erp\Services;

use App\Contracts\Erp\ErpAuthInterface;
use App\Contracts\Erp\ErpServiceInterface;
use App\Contracts\Erp\ErpRequestBuilderInterface;
use App\Contracts\Erp\ErpResponseProcessorInterface;
use App\Exceptions\Erp\ErpServiceException;
use App\Models\CompanyErpSetting;
use App\Services\Erp\Response\ErpServiceResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Classe base abstrata para todos os serviços ERP
 * Fornece funcionalidades comuns e estrutura padrão
 */
abstract class BaseErpService implements ErpServiceInterface
{
    protected CompanyErpSetting $settings;
    protected ErpAuthInterface $authHandler;
    protected ErpRequestBuilderInterface $requestBuilder;
    protected ErpResponseProcessorInterface $responseProcessor;
    protected int $timeout = 30;
    protected int $retryCount = 3;
    protected array $defaultHeaders = [];

    public function __construct(
        CompanyErpSetting $settings,
        ErpAuthInterface $authHandler,
        ErpRequestBuilderInterface $requestBuilder,
        ErpResponseProcessorInterface $responseProcessor
    ) {
        $this->settings = $settings;
        $this->authHandler = $authHandler;
        $this->requestBuilder = $requestBuilder;
        $this->responseProcessor = $responseProcessor;
        $this->initializeService();
    }

    /**
     * Método para inicialização específica do serviço
     */
    protected function initializeService(): void
    {
        // Override se necessário
    }

    /**
     * Execução principal do serviço
     */
    public function execute(array $params = []): ErpServiceResponse
    {
        $startTime = microtime(true);

        try {
            // Validação de parâmetros
            if (!$this->validateParams($params)) {
                return $this->createErrorResponse(
                    'Parâmetros inválidos. Parâmetros obrigatórios: ' . implode(', ', $this->getRequiredParams())
                );
            }

            // Preparação dos parâmetros
            $params = $this->prepareParams($params);

            // Autenticação
            if (!$this->ensureAuthenticated()) {
                return $this->createErrorResponse('Falha na autenticação do ERP');
            }

            // Execução do serviço
            $response = $this->performServiceWithRetry($params);

            // Log de sucesso
            $this->logExecution(true, $params, microtime(true) - $startTime);

            return $response;

        } catch (\Exception $e) {
            // Log de erro
            $this->logExecution(false, $params ?? [], microtime(true) - $startTime, $e);

            return $this->createErrorResponse($e->getMessage());
        }
    }

    /**
     * Executa o serviço com retry automático em caso de falha
     */
    protected function performServiceWithRetry(array $params): ErpServiceResponse
    {
        $lastException = null;

        for ($attempt = 1; $attempt <= $this->retryCount; $attempt++) {
            try {
                return $this->performService($params);
            } catch (\Exception $e) {
                $lastException = $e;

                // Se não é erro de conectividade/timeout, não tenta novamente
                if (!$this->shouldRetry($e, $attempt)) {
                    throw $e;
                }

                // Aguarda antes de tentar novamente
                if ($attempt < $this->retryCount) {
                    sleep($attempt); // Backoff exponencial simples
                }
            }
        }

        throw $lastException;
    }

    /**
     * Determina se deve tentar novamente baseado no erro
     */
    protected function shouldRetry(\Exception $e, int $attempt): bool
    {
        if ($attempt >= $this->retryCount) {
            return false;
        }

        // Retry em casos de timeout, conexão ou erros temporários
        return str_contains(strtolower($e->getMessage()), 'timeout') ||
            str_contains(strtolower($e->getMessage()), 'connection') ||
            str_contains(strtolower($e->getMessage()), 'temporary');
    }

    /**
     * Garante que está autenticado
     */
    protected function ensureAuthenticated(): bool
    {
        if (!$this->authHandler->isTokenValid()) {
            return $this->authHandler->authenticate();
        }
        return true;
    }

    /**
     * Prepara os parâmetros antes da execução
     */
    protected function prepareParams(array $params): array
    {
        // Adiciona parâmetros padrão se necessário
        return array_merge($this->getDefaultParams(), $params);
    }

    /**
     * Retorna parâmetros padrão para o serviço
     */
    protected function getDefaultParams(): array
    {
        return [];
    }

    /**
     * Executa uma requisição HTTP usando o builder
     */
    protected function executeRequest(array $requestConfig): mixed
    {
        $http = Http::timeout($this->timeout);

        if (isset($requestConfig['headers'])) {
            $http = $http->withHeaders($requestConfig['headers']);
        }

        $method = strtolower($requestConfig['method'] ?? 'post');
        $url = $requestConfig['url'];
        $body = $requestConfig['body'] ?? [];

        return match($method) {
            'get' => $http->get($url, $body),
            'post' => $http->post($url, $body),
            'put' => $http->put($url, $body),
            'patch' => $http->patch($url, $body),
            'delete' => $http->delete($url, $body),
            default => throw new ErpServiceException("Método HTTP não suportado: {$method}")
        };
    }

    /**
     * Cria resposta de erro padronizada
     */
    protected function createErrorResponse(string $message): ErpServiceResponse
    {
        return new ErpServiceResponse(false, null, $message);
    }

    /**
     * Log da execução do serviço
     */
    protected function logExecution(bool $success, array $params, float $executionTime, ?\Exception $exception = null): void
    {
        $logData = [
            'service' => $this->getServiceName(),
            'company_id' => $this->settings->company_id,
            'success' => $success,
            'execution_time_ms' => round($executionTime * 1000, 2),
            'params_count' => count($params),
        ];

        if ($exception) {
            $logData['error'] = $exception->getMessage();
            $logData['exception_class'] = get_class($exception);
        }

        if ($success) {
            Log::info("Serviço ERP executado com sucesso", $logData);
        } else {
            Log::error("Falha na execução do serviço ERP", $logData);
        }
    }

    /**
     * Validação de parâmetros
     */
    public function validateParams(array $params): bool
    {
        $required = $this->getRequiredParams();
        foreach ($required as $param) {
            if (!isset($params[$param]) || $params[$param] === null || $params[$param] === '') {
                return false;
            }
        }
        return true;
    }

    // Métodos abstratos que devem ser implementados pelas classes filhas
    abstract protected function performService(array $params): ErpServiceResponse;
    abstract public function getServiceName(): string;
    abstract public function getRequiredParams(): array;

    // Getters e Setters
    public function setTimeout(int $timeout): self
    {
        $this->timeout = $timeout;
        return $this;
    }

    public function setRetryCount(int $retryCount): self
    {
        $this->retryCount = $retryCount;
        return $this;
    }

    public function setDefaultHeaders(array $headers): self
    {
        $this->defaultHeaders = $headers;
        return $this;
    }
}