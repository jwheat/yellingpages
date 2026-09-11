<?php

namespace App\Providers;

use App\Scrubbing\Contracts\RantScrubber;
use App\Scrubbing\ScrubManager;
use Illuminate\Support\ServiceProvider;

class ScrubServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ScrubManager::class, fn ($app) => new ScrubManager($app));

        // Typehinting RantScrubber resolves the configured default driver.
        $this->app->bind(RantScrubber::class, fn ($app) => $app->make(ScrubManager::class)->driver());
    }
}
