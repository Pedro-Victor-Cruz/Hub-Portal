<?php

namespace App\Services\Erp\Drivers\Sankhya;

use App\Contracts\Erp\ErpAuthInterface;
use App\Contracts\Erp\ErpIntegrationInterface;
use App\Models\CompanyErpSetting;
use App\Services\Erp\Core\ErpAuthFactory;


/**
 * Driver atualizado para integração com o ERP Sankhya
 */
class SankhyaDriver implements ErpIntegrationInterface
{
    private CompanyErpSetting $settings;
    private ?ErpAuthInterface $authHandler = null;



    public function __construct(CompanyErpSetting $settings)
    {
        $this->settings = $settings;
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

}