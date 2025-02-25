<?php

namespace App\Providers;

// use Illuminate\Support\Facades\Gate;
use App\Guards\AuthGuard;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Auth;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        //
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        // Registrar o provider personalizado
        Auth::provider('auth_provider', function ($app, array $config) {
            return new AuthProvider($app->make('hash'), $config['model']);
        });

        // Registrar o guard personalizado
        Auth::extend('auth_guard', function ($app, $name, array $config) {
            $provider = Auth::createUserProvider($config['provider']);
            if ($provider instanceof AuthProvider)
                return new AuthGuard($provider, $app->make('request'));
            throw new \InvalidArgumentException('AuthGuard is not a valid provider.');
        });
    }
}
