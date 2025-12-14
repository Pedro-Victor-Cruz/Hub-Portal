<?php

namespace App\Services\Core\Integration;


use App\Enums\IntegrationType;
use App\Models\Integration;
use App\Services\Core\ApiResponse;
use App\Services\Core\BaseService;
use App\Services\Core\Traits\HasAuthentication;
use Exception;

abstract class IntegrationService extends BaseService
{
    protected BaseIntegration $integration;
    protected IntegrationType $requiredIntegrationType;

    public function __construct(?IntegrationType $integrationType = null)
    {
        parent::__construct();

        $integrationType = $integrationType ?? $this->requiredIntegrationType;

        if (!$integrationType) {
            throw new Exception('Tipo de integração não especificado.');
        }

        $integrationModel = Integration::type($integrationType)->first();

        if (!$integrationModel) {
            throw new Exception("Integração do tipo {$integrationType->value} não encontrada.");
        }

        $this->integration = app(IntegrationManager::class)->getDriver($integrationModel);
    }

    public function execute(array $params = []): ApiResponse
    {
        try {
            // Autentica automaticamente se a integração suporta
            if ($this->integration instanceof HasAuthentication || method_exists($this->integration, 'authenticate')) {
                if (!$this->integration->authenticate()) {
                    return $this->error('Falha na autenticação com a integração');
                }
            }

            return $this->performService($params);

        } catch (Exception $e) {
            return $this->error(
                'Erro na execução do serviço',
                [$e->getMessage()],
                [
                    'integration_type' => $this->requiredIntegrationType,
                    'file' => basename($e->getFile()),
                    'line' => $e->getLine(),
                ]
            );
        }
    }

    abstract protected function performService(array $params): ApiResponse;
}