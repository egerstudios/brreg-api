<?php

namespace Egerstudios\BrregApi;

use Illuminate\Support\ServiceProvider;
use Egerstudios\BrregApi\Services\BrregApiService;

class BrregApiServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/brreg.php', 'brreg'
        );

        $this->app->singleton(BrregApiService::class, function ($app) {
            return new BrregApiService(
                $app['config']['brreg'],
                $app['cache.store']
            );
        });

        $this->app->alias(BrregApiService::class, 'brreg-api');
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/brreg.php' => config_path('brreg.php'),
            ], 'brreg-config');
        }
    }
}