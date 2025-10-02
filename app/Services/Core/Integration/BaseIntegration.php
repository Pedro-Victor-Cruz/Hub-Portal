<?php

namespace App\Services\Core\Integration;

use App\Contracts\Integration\IntegrationInterface;
use App\Models\Integration;
use App\Services\Core\ApiResponse;
use App\Services\Parameter\ServiceParameterManager;
use Exception;

/**
 * Classe base para todas as integrações
 * Fornece funcionalidades comuns como gerenciamento de configurações e parâmetros
 */
abstract class BaseIntegration implements IntegrationInterface
{
    protected Integration $integration;
    protected ServiceParameterManager $parameterManager;
    protected string $name = '';
    protected string $description = '';
    protected string $version = '1.0.0';
    protected string $image = '';

    public function __construct(Integration $integration)
    {
        $this->integration = $integration;
        $this->parameterManager = new ServiceParameterManager();
        $this->configureParameters();
    }

    /**
     * Obtém o nome da integração
     */
    public function getName(): string
    {
        return $this->name ?: class_basename(static::class);
    }

    /**
     * Obtém a descrição da integração
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * Obtém a versão da integração
     */
    public function getVersion(): string
    {
        return $this->version;
    }

    /**
     * Obtém o gerenciador de parâmetros
     */
    public function getParameterManager(): ServiceParameterManager
    {
        return $this->parameterManager;
    }

    /**
     * Obtém todos os parâmetros configurados
     */
    public function getParameters(): array
    {
        return $this->parameterManager->getGrouped();
    }

    /**
     * Obtém uma configuração específica
     */
    public function getConfig(string $key, mixed $default = null): mixed
    {
        return $this->integration->getConfig($key, $default);
    }


    /**
     * Valida as configurações da integração
     */
    public function validateConfiguration(array $config = null): array
    {
        $configToValidate = $config ?? $this->integration->configuration ?? [];
        return $this->parameterManager->validate($configToValidate);
    }

    /**
     * Testa a conexão com a integração
     */
    public function testConnection(): ApiResponse
    {
        $startTime = microtime(true);

        try {
            // Valida configurações primeiro
            $validation = $this->validateConfiguration();
            if (!$validation['valid']) {
                return ApiResponse::error(
                    'Configurações inválidas',
                    $validation['errors']
                );
            }

            // Executa o teste específico da integração
            $result = $this->performConnectionTest();
            $responseTime = round((microtime(true) - $startTime) * 1000, 2);

            return ApiResponse::success([
                'connected' => $result['success'],
                'response_time_ms' => $responseTime,
                'details' => $result['details'] ?? [],
                'timestamp' => now()->toISOString(),
            ], 'Teste de conexão realizado');

        } catch (Exception $e) {
            $responseTime = round((microtime(true) - $startTime) * 1000, 2);

            return ApiResponse::error(
                'Erro no teste de conexão',
                [$e->getMessage()],
                [
                    'response_time_ms' => $responseTime,
                    'file' => basename($e->getFile()),
                    'line' => $e->getLine(),
                    'timestamp' => now()->toISOString(),
                ]
            );
        }
    }

    /**
     * Sincroniza dados com a integração
     */
    public function syncData(array $options = []): ApiResponse
    {
        try {
            $result = $this->performSync($options);

            // Atualiza status de sincronização
            $this->integration->updateSyncStatus([
                'success' => $result->isSuccess(),
                'message' => $result->getMessage(),
                'details' => $result->getMetadata(),
            ]);

            return $result;

        } catch (Exception $e) {
            $this->integration->updateSyncStatus([
                'success' => false,
                'message' => $e->getMessage(),
                'error_details' => [
                    'file' => basename($e->getFile()),
                    'line' => $e->getLine(),
                ],
            ]);

            return ApiResponse::error(
                'Erro na sincronização',
                [$e->getMessage()]
            );
        }
    }

    /**
     * Obtém informações gerais da integração
     */
    public function getInfo(): array
    {
        return [
            'name' => $this->getName(),
            'description' => $this->getDescription(),
            'version' => $this->getVersion(),
            'is_active' => $this->integration->isActive(),
            'last_sync' => $this->integration->last_sync_at?->toISOString(),
            'sync_status' => $this->integration->sync_status,
            'parameters' => $this->getParameters(),
        ];
    }

    /**
     * Configura os parâmetros específicos da integração
     * Deve ser implementado pelas classes filhas
     */
    abstract public function configureParameters(): void;

    /**
     * Executa o teste de conexão específico da integração
     * Deve ser implementado pelas classes filhas
     */
    abstract public function performConnectionTest(): array;

    /**
     * Executa a sincronização específica da integração
     * Deve ser implementado pelas classes filhas se suportar sincronização
     */
    public function performSync(array $options = []): ApiResponse
    {
        return ApiResponse::error(
            'Sincronização não implementada',
            ['Esta integração não suporta sincronização automática']
        );
    }

    /**
     * Obtém o logo/imagem da integração
     */
    public function getImage(): string
    {
        return $this->image
            ? asset('integrations/images/' . $this->image)
            : asset('integrations/images/default-integration.png');
    }

}