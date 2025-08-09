<?php

namespace App\Facades;

use App\Contracts\Erp\ErpIntegrationInterface;
use App\Models\Company;
use Illuminate\Support\Facades\Facade;

/**
 * @method static ErpIntegrationInterface forCompany(Company $company)
 * @method static array testConnection(Company $company)
 * @see \App\Services\Erp\Core\ErpManager
 */
class ErpManager extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \App\Services\Erp\Core\ErpManager::class;
    }
}