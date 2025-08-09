<?php

namespace App\Providers;

use App\Repositories\Erp\ErpSettingsRepository;
use App\Services\Erp\Core\ErpManager;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(ErpManager::class, function ($app) {
            return new ErpManager(
                $app->make(ErpSettingsRepository::class)
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
