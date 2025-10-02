<?php

namespace App\Facades;

use App\Models\Company;
use App\Models\Integration;
use App\Services\Core\ApiResponse;
use Illuminate\Support\Facades\Facade;

/**
 * @method static array getAvailableIntegrations()
 * @method static array|null getIntegrationInfo(string $integrationName)
 * @method static ApiResponse createIntegration(Company $company, string $integrationName, array $configuration, bool $active = true)
 * @method static ApiResponse updateIntegration(Integration $integration, array $configuration, bool|null $active = null)
 * @method static \App\Services\Core\Integration\BaseIntegration getDriver(Integration $integration)
 * @method static \App\Services\Core\Integration\BaseIntegration|null getDriverForCompany(Company $company, string $integrationName)
 * @method static ApiResponse testConnection(Integration $integration)
 * @method static ApiResponse syncData(Integration $integration, array $options = [])
 * @method static array getCompanyIntegrations(Company $company, bool $activeOnly = false)
 * @method static ApiResponse deleteIntegration(Integration $integration)
 * @method static ApiResponse toggleIntegration(Integration $integration, bool $active)
 * @method static array getIntegrationsStats(Company $company)
 *
 * @see \App\Services\Core\Integration\IntegrationManager
 */
class IntegrationManager extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return \App\Services\Core\Integration\IntegrationManager::class;
    }
}