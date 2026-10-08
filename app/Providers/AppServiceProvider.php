<?php

namespace App\Providers;

use App\Models\Business;
use App\Models\User;
use App\Services\Auth\AuthorizationService;
use App\Services\Tenancy\TenantContext;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(TenantContext::class, function () {
            return new TenantContext;
        });

        $this->app->singleton(AuthorizationService::class, function ($app) {
            return new AuthorizationService($app->make(TenantContext::class));
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::before(function (User $user, string $ability, array $arguments = []) {
            $authService = app(AuthorizationService::class);
            $businessContext = isset($arguments[0]) && $arguments[0] instanceof Business ? $arguments[0] : null;

            return $authService->can($user, $ability, $businessContext);
        });
    }
}
