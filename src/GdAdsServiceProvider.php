<?php

namespace Packitifotech\Laragdads;

use Illuminate\Support\ServiceProvider;

class GdAdsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/laragdads.php' => config_path('laragdads.php'),
        ], 'laragdads-config');
    }

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/laragdads.php', 'laragdads');
    }
}
