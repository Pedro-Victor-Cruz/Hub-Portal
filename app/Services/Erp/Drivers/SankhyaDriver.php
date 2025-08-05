<?php

namespace App\Services\Erp\Drivers;

use App\Contracts\Erp\ErpAuthInterface;
use App\Contracts\Erp\ErpIntegrationInterface;
use App\Contracts\Erp\ErpServiceInterface;
use App\Exceptions\Erp\ErpServiceNotSupportedException;
use App\Models\CompanyErpSetting;
use App\Services\Erp\Drivers\Handlers\Auth\ErpAuthFactory;
use App\Services\Erp\Request\SankhyaRequestBuilder;
use App\Services\Erp\Response\SankhyaResponseProcessor;
use Illuminate\Support\Facades\Log;

/**
 * Driver atualizado para integração com o ERP Sankhya
 */
class SankhyaDriver implements ErpIntegrationInterface
{
    private CompanyErpSetting $settings;
    private ?ErpAuthInterface $authHandler = null;
    private array $serviceHandlers = [];
    private SankhyaRequestBuilder $requestBuilder;
    private SankhyaResponseProcessor $responseProcessor;

    /**
     * Mapeamento de serviços suportados
     */
    private const SUPPORTED_SERVICES = [
        'QUERY' => \App\Services\Erp\Services\Sankhya\SankhyaQueryService::class,
    ];

    public function __construct(CompanyErpSetting $settings)
    {
        $this->settings = $settings;
        $this->requestBuilder = new SankhyaRequestBuilder($settings->base_url);
        $this->responseProcessor = new SankhyaResponseProcessor();

        Log::info("Inicializando driver Sankhya com nova arquitetura", [
            'company_id' => $settings->company_id,
            'auth_type' => $settings->auth_type,
            'erp_setting_id' => $settings->id
        ]);
    }

    public function authenticate(): bool
    {
        try {
            return $this->getAuthHandler()->authenticate();
        } catch (\Exception $e) {
            Log::error("Falha na autenticação do driver Sankhya", [
                'company_id' => $this->settings->company_id,
                'auth_type' => $this->settings->auth_type,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function getSettings(): CompanyErpSetting
    {
        return $this->settings;
    }

    public function getAuthHandler(): ErpAuthInterface
    {
        if (!$this->authHandler) {
            $this->authHandler = ErpAuthFactory::create($this->settings);
        }
        return $this->authHandler;
    }

    public function getServiceHandler(string $serviceName): ErpServiceInterface
    {
        if (!isset(self::SUPPORTED_SERVICES[$serviceName])) {
            throw new ErpServiceNotSupportedException($serviceName, $this->settings->erp_name);
        }

        if (!isset($this->serviceHandlers[$serviceName])) {
            $serviceClass = self::SUPPORTED_SERVICES[$serviceName];
            $this->serviceHandlers[$serviceName] = new $serviceClass(
                $this->settings,
                $this->getAuthHandler(),
                $this->requestBuilder,
                $this->responseProcessor
            );
        }

        return $this->serviceHandlers[$serviceName];
    }

    public function getSupportedServices(): array
    {
        return array_keys(self::SUPPORTED_SERVICES);
    }
}