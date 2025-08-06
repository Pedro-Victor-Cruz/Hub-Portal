<?php

namespace App\Services\Erp\Drivers\Sankhya;

use App\Contracts\Erp\ErpAuthInterface;
use App\Contracts\Erp\ErpIntegrationInterface;
use App\Contracts\Erp\ErpServiceInterface;
use App\Exceptions\Erp\ErpServiceNotSupportedException;
use App\Models\CompanyErpSetting;
use App\Services\Erp\Core\ErpAuthFactory;
use App\Services\Erp\Drivers\Sankhya\Request\SankhyaRequestBuilder;
use App\Services\Erp\Drivers\Sankhya\Response\SankhyaResponseProcessor;
use App\Services\Erp\Drivers\Sankhya\Services\SankhyaQueryService;

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
        'QUERY' => SankhyaQueryService::class,
    ];

    public function __construct(CompanyErpSetting $settings)
    {
        $this->settings = $settings;
        $this->requestBuilder = new SankhyaRequestBuilder($settings->base_url);
        $this->responseProcessor = new SankhyaResponseProcessor();
    }

    public function authenticate(): bool
    {
        try {
            return $this->getAuthHandler()->authenticate();
        } catch (\Exception $e) {
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