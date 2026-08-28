<?php

declare(strict_types=1);

namespace DcbLk;

use Illuminate\Support\ServiceProvider;

class DcbLkServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/dcb-lk.php', 'dcb-lk');

        $this->app->singleton(DcbManager::class, fn ($app) => new DcbManager($app));
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/dcb-lk.php' => config_path('dcb-lk.php'),
            ], 'dcb-lk-config');
        }
    }
}
