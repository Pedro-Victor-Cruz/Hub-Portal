<?php

namespace App\Facades;

use App\Contracts\Services\ServiceInterface;
use App\Enums\ServiceType;
use App\Services\Core\ApiResponse;
use Illuminate\Support\Facades\Facade;


/**
 * Facade para o gerenciador de serviços
 *
 * @method static ApiResponse executeService(string $serviceName, array $params = [])
 * @method static ApiResponse executeServiceByClass(string $serviceClass, array $params = [])
 * @method static array getServicesByType(ServiceType $serviceType)
 * @method static array getAllServices()
 * @method static bool serviceExists(string $slug)
 * @method static void clearCache()
 * @method static array getServiceInfo(string $slug)
 * @method static ServiceInterface getServiceInstance(string $slug)
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