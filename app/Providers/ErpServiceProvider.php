<?php

namespace App\Providers;

use App\Repositories\Erp\ErpSettingsRepository;
use App\Services\Erp\Core\ErpManager;
use Illuminate\Support\ServiceProvider;

class ErpServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ErpManager::class, function ($app) {
            return new ErpManager(
                $app->make(ErpSettingsRepository::class)
            );
        });

        // Registrar drivers dinamicamente da configuração
        foreach (config('erp.drivers') as $driverClass) {
            $this->app->bind($driverClass, function ($app, $params) use ($driverClass) {
                return new $driverClass($params['settings']);
            });
        }
    }
}