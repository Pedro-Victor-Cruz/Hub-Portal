<?php

namespace App\Facades;

use App\Contracts\Services\ServiceInterface;
use App\Enums\ServiceType;
use App\Models\Company;
use App\Services\Core\ApiResponse;
use Illuminate\Support\Facades\Facade;


/**
 * Facade para o gerenciador de serviços
 *
 * @method static ApiResponse executeService(string $serviceName, array $params = [], ?Company $company = null)
 * @method static ApiResponse executeServiceByClass(string $serviceClass, array $params = [], ?Company $company = null)
 * @method static array getServicesByType(ServiceType $serviceType, ?Company $company = null)
 * @method static array getAllServices(?Company $company = null)
 * @method static bool serviceExists(string $slug)
 * @method static void clearCache()
 * @method static array getServiceInfo(string $slug, ?Company $company = null)
 * @method static ServiceInterface getServiceInstance(string $slug, ?Company $company = null)
 * @method static bool existsService(string $slug)
 * @method static array getServiceParameters(string $serviceSlug)
 */
class ServiceManager extends Facade
{

    protected static function getFacadeAccessor(): string
    {
        return \App\Services\Core\ServiceManager::class;
    }
}