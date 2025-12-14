<?php

namespace App\Facades;

use App\Models\DynamicQuery;
use App\Services\Core\ApiResponse;
use Illuminate\Support\Facades\Facade;

/**
 * Facade para DynamicQueryManager
 *
 * @method static ApiResponse executeQuery(string $key, array $additionalParams = [])
 * @method static array getAvailableQueries()
 * @method static ApiResponse createQuery(array $data)
 * @method static ApiResponse updateQuery(string $key, array $data)
 * @method static ApiResponse deleteQuery(string $key)
 * @method static ApiResponse testQuery(array $queryData, array $testParams = [])
 * @method static ApiResponse validateQueryExecution(string $key)
 * @method static array extractRequiredParams(DynamicQuery $query)
 */
class DynamicQueryManager extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \App\Services\Query\DynamicQueryManager::class;
    }
}