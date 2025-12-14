<?php

namespace App\Services\Core\Integration;

use App\Enums\IntegrationType;
use App\Models\Integration;
use App\Services\Core\ApiResponse;
use Exception;

/**
 * Gerenciador central para todas as integrações
 * Responsável por instanciar, gerenciar e orquestrar as integrações
 */
class IntegrationManager
{
    private array $drivers = [];
    private array $availableIntegrations = [];

    public function __construct()
    {
        $this->loadAvailableIntegrations();
    }

    /**
     * Carrega as integrações disponíveis do arquivo de configuração
     */
    private function loadAvailableIntegrations(): void
    {
        $this->availableIntegrations = config('integration.drivers', []);
    }

    /**
     * Retorna todas as integrações disponíveis
     */
    public function getAvailableIntegrations(): array
    {
        $integrations = [];

        foreach ($this->availableIntegrations as $name => $className) {
            try {
                if (class_exists($className)) {
                    // Cria uma instância temporária para obter as informações
                    $tempIntegration = new Integration([
                        'integration_name' => $name,
                        'configuration' => [],
                        'active' => false,
                    ]);

                    $instance = new $className($tempIntegration);
                    $integrations[] = [
                        'integration_name' => $name,
                        'name' => $instance->getName(),
                        'img' => $instance->getImage(),
                        'description' => $instance->getDescription(),
                        'version' => $instance->getVersion(),
                        'parameters' => $instance->getParameters()
                    ];
                }
            } catch (Exception $e) {
                // Log do erro, mas continua para outras integrações
                logger()->warning("Erro ao carregar integração {$name}: " . $e->getMessage());
            }
        }

        return $integrations;
    }

    /**
     * Obtém informações de uma integração específica
     */
    public function getIntegrationInfo(string $integrationName): ?array
    {
        $available = $this->getAvailableIntegrations();
        return collect($available)->firstWhere('integration_name', $integrationName);
    }


    public function createIntegration(
        string $integrationName,
        array $configuration,
        bool $active = true
    ): ApiResponse {
        try {
            // Verifica se a integração é válida
            if (!isset($this->availableIntegrations[$integrationName])) {
                return ApiResponse::error(
                    'Integração inválida',
                    ["A integração '{$integrationName}' não é suportada"]
                );
            }

            // Verifica se já existe uma integração deste tipo para a empresa
            $integrationType = IntegrationType::tryFrom($integrationName);
            $existing = Integration::type($integrationType)->first();

            if ($existing) {
                return ApiResponse::error(
                    'Integração já existe',
                    ["Já existe uma integração do tipo '{$integrationName}'"]
                );
            }

            // Cria o registro da integração
            $integration = new Integration([
                'integration_name' => $integrationName,
                'configuration' => $configuration,
                'active' => $active,
            ]);

            // Valida a configuração
            $driver = $this->getDriver($integration);
            $validation = $driver->validateConfiguration($configuration);

            if (!$validation['valid']) {
                return ApiResponse::error(
                    'Configuração inválida',
                    $validation['errors'],
                    ['configuration' => $configuration]
                );
            }

            // Salva com configuração validada/sanitizada
            $integration->configuration = $validation['sanitized'];
            $integration->save();

            return ApiResponse::success(
                $integration->toArray(),
                'Integração criada com sucesso'
            );

        } catch (Exception $e) {
            return ApiResponse::error(
                'Erro ao criar integração',
                [$e->getMessage()],
                [
                    'file' => basename($e->getFile()),
                    'line' => $e->getLine(),
                ]
            );
        }
    }

    /**
     * Atualiza uma integração existente
     */
    public function updateIntegration(
        Integration $integration,
        array $configuration,
        ?bool $active = null
    ): ApiResponse {
        try {
            // Valida a nova configuração
            $driver = $this->getDriver($integration);
            $validation = $driver->validateConfiguration($configuration);

            if (!$validation['valid']) {
                return ApiResponse::error(
                    'Configuração inválida',
                    $validation['errors']
                );
            }

            // Atualiza os dados
            $integration->configuration = $validation['sanitized'];
            if ($active !== null) {
                $integration->active = $active;
            }
            $integration->save();

            return ApiResponse::success(
                $integration->toArray(),
                'Integração atualizada com sucesso'
            );

        } catch (Exception $e) {
            return ApiResponse::error(
                'Erro ao atualizar integração',
                [$e->getMessage()]
            );
        }
    }

    /**
     * Obtém o driver de uma integração
     */
    public function getDriver(Integration $integration): BaseIntegration
    {
        $cacheKey = $integration->id;

        if (!isset($this->drivers[$cacheKey])) {
            $className = $this->availableIntegrations[$integration->integration_name] ?? null;

            if (!$className) {
                throw new Exception("Classe da integração não encontrada para: {$integration->integration_name}");
            }

            if (!class_exists($className)) {
                throw new Exception("Classe não existe: {$className}");
            }

            $this->drivers[$cacheKey] = new $className($integration);

            if (!$this->drivers[$cacheKey] instanceof BaseIntegration) {
                throw new Exception("A classe deve estender BaseIntegration: {$className}");
            }
        }

        return $this->drivers[$cacheKey];
    }


    /**
     * Testa conexão de uma integração
     */
    public function testConnection(Integration $integration): ApiResponse
    {
        try {
            $driver = $this->getDriver($integration);
            return $driver->testConnection();

        } catch (Exception $e) {
            return ApiResponse::error(
                'Erro no teste de conexão',
                [$e->getMessage()]
            );
        }
    }

    /**
     * Sincroniza dados de uma integração
     */
    public function syncData(Integration $integration, array $options = []): ApiResponse
    {
        try {
            $driver = $this->getDriver($integration);
            return $driver->syncData($options);

        } catch (Exception $e) {
            return ApiResponse::error(
                'Erro na sincronização',
                [$e->getMessage()]
            );
        }
    }

    /**
     * Remove uma integração
     */
    public function deleteIntegration(Integration $integration): ApiResponse
    {
        try {
            $integration->delete();

            return ApiResponse::success(
                null,
                'Integração removida com sucesso'
            );

        } catch (Exception $e) {
            return ApiResponse::error(
                'Erro ao remover integração',
                [$e->getMessage()]
            );
        }
    }

    /**
     * Ativa/desativa uma integração
     */
    public function toggleIntegration(Integration $integration, bool $active): ApiResponse
    {
        try {
            $integration->active = $active;
            $integration->save();

            $status = $active ? 'ativada' : 'desativada';

            return ApiResponse::success(
                $integration->toArray(),
                "Integração {$status} com sucesso"
            );

        } catch (Exception $e) {
            return ApiResponse::error(
                'Erro ao alterar status da integração',
                [$e->getMessage()]
            );
        }
    }

    /**
     * Obtém estatísticas das integrações
     */
    public function getIntegrationsStats(): array
    {
        $integrations = Integration::all();
        $active = $integrations->where('active', true);
        $recentSync = $integrations->filter(function ($integration) {
            return $integration->hasRecentSuccessfulSync(60); // últimos 60 minutos
        });

        return [
            'total' => $integrations->count(),
            'active' => $active->count(),
            'inactive' => $integrations->count() - $active->count(),
            'recently_synced' => $recentSync->count(),
            'by_type' => $integrations->groupBy('integration_name')
                ->map(function ($group) {
                    return $group->count();
                })
                ->toArray(),
        ];
    }
}