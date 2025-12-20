<?php

namespace App\Providers;

use App\Models\Dashboard;
use App\Observers\DashboardObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {

    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Dashboard::observe(DashboardObserver::class);
    }
}
