<?php

namespace App\Facades;

use App\Models\Company;
use App\Services\Core\ApiResponse;
use Illuminate\Support\Facades\Facade;

/**
 * Facade para DynamicQueryManager
 *
 * @method static ApiResponse executeQuery(string $key, ?Company $company = null, array $additionalParams = [])
 * @method static array getAvailableQueries(?Company $company = null)
 * @method static ApiResponse createQuery(array $data)
 * @method static ApiResponse updateQuery(string $key, array $data, ?Company $company = null)
 * @method static ApiResponse deleteQuery(string $key, ?Company $company = null)
 * @method static ApiResponse duplicateQueryForCompany(string $key, Company $company, array $overrides = [])
 * @method static ApiResponse testQuery(array $queryData, array $testParams = [])
 * @method static ApiResponse validateQueryExecution(string $key, ?Company $company = null)
 */
class DynamicQueryManager extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \App\Services\Query\DynamicQueryManager::class;
    }
}