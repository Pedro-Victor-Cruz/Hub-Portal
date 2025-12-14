<?php

namespace App\Facades;

use App\Models\Integration;
use App\Services\Core\ApiResponse;
use App\Services\Core\Integration\BaseIntegration;
use Illuminate\Support\Facades\Facade;

/**
 * @method static array getAvailableIntegrations()
 * @method static array|null getIntegrationInfo(string $integrationName)
 * @method static ApiResponse createIntegration(string $integrationName, array $configuration, bool $active = true)
 * @method static ApiResponse updateIntegration(Integration $integration, array $configuration, bool|null $active = null)
 * @method static BaseIntegration getDriver(Integration $integration)
 * @method static ApiResponse testConnection(Integration $integration)
 * @method static ApiResponse syncData(Integration $integration, array $options = [])
 * @method static ApiResponse deleteIntegration(Integration $integration)
 * @method static ApiResponse toggleIntegration(Integration $integration, bool $active)
 * @method static array getIntegrationsStats()
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