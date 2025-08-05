<?php

namespace App\Contracts\Erp;

use App\Models\CompanyErpSetting;

interface ErpIntegrationInterface
{
    public function authenticate(): bool;
    public function getSettings(): CompanyErpSetting;
    public function getAuthHandler(): ErpAuthInterface;
    public function getServiceHandler(string $serviceName): ErpServiceInterface;
    public function getSupportedServices(): array;
}