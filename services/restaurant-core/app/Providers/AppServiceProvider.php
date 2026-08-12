<?php

namespace App\Providers;

use App\Domain\System\Services\InstallationIdentityService;
use App\Domain\System\Services\InternetConnectivityService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(InstallationIdentityService::class, fn () => new InstallationIdentityService(
            config('system.installation_id_path'),
        ));

        $this->app->singleton(InternetConnectivityService::class, fn () => new InternetConnectivityService(
            config('system.internet_check.targets'),
            config('system.internet_check.timeout'),
        ));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
