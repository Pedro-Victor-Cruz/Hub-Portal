<?php

namespace App\Services\Erp\Core;

use App\Contracts\Erp\ErpIntegrationInterface;
use App\Models\Company;
use App\Models\CompanyErpSetting;
use App\Repositories\Erp\ErpSettingsRepository;

/**
 * @internal
 */
class ErpManager
{

    private array $drivers = [];
    private ErpSettingsRepository $repository;

    public function __construct(ErpSettingsRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Retorna o driver de ERP para a empresa especificada.
     *
     * @param Company $company
     * @return ErpIntegrationInterface
     * @throws \Exception
     */
    public function forCompany(Company $company): ErpIntegrationInterface
    {
        if (!$erpSettings = $company->activeErpSetting()) {
            throw new \Exception("Não há configuração de ERP ativa para a empresa: {$company->key}");
        }

        return $this->getDriver($erpSettings);
    }

    /**
     * Retorna o driver de ERP baseado nas configurações fornecidas.
     *
     * @param CompanyErpSetting $erpSettings
     * @return ErpIntegrationInterface
     * @throws \Exception
     */
    public function getDriver(CompanyErpSetting $erpSettings): ErpIntegrationInterface
    {
        $driverClass = $erpSettings->getDriverClass();
        $cacheKey = $erpSettings->company_id . '_' . $erpSettings->id;

        if (!$driverClass) {
            throw new \Exception("Classe do driver de ERP não configurada para a empresa: {$erpSettings->company_id}");
        }

        if (!$erpSettings->base_url) {
            throw new \Exception("Base URL do ERP não configurada para a empresa: {$erpSettings->company_id}");
        }

        if (!isset($this->drivers[$cacheKey])) {
            $this->drivers[$cacheKey] = app($driverClass, ['settings' => $erpSettings]);
        }

        if (!class_exists($driverClass)) {
            throw new \Exception("Classe do driver de ERP não encontrada: {$driverClass}");
        }

        if (!$this->drivers[$cacheKey] instanceof ErpIntegrationInterface) {
            throw new \Exception("O driver de ERP não implementa a interface esperada: {$driverClass}");
        }

        /** @var ErpIntegrationInterface */
        return $this->drivers[$cacheKey];
    }


    public function testConnection(Company $company): array
    {
        $startTime = microtime(true);
        $timestamp = now()->toISOString();

        try {
            $driver = $this->forCompany($company);
            $authHandler = $driver->getAuthHandler();

            $authResult = $authHandler->authenticate();
            $responseTime = round((microtime(true) - $startTime) * 1000, 2);

            return [
                'success' => $authResult,
                'response_time_ms' => $responseTime,
                'auth_type' => $authHandler->getAuthType(),
                'token_valid' => $authResult ? $authHandler->isTokenValid() : null,
                'timestamp' => $timestamp,
            ];
        } catch (\Throwable $e) {
            $responseTime = round((microtime(true) - $startTime) * 1000, 2);

            return [
                'success' => false,
                'error' => $e->getMessage() . ' | Linha: ' . $e->getLine(),
                'response_time_ms' => $responseTime,
                'auth_type' => isset($authHandler) ? $authHandler->getAuthType() : null,
                'timestamp' => $timestamp,
            ];
        }
    }

}