<?php

namespace App\Providers;

use App\Services\RateProvider\Factories\RateProviderClientFactory;
use App\Services\RateProvider\GuzzleRateProviderClient;
use App\Services\RateProvider\RateProviderService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(
            RateProviderService::class,
            static fn () => new RateProviderService(new GuzzleRateProviderClient(RateProviderClientFactory::make())),
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
