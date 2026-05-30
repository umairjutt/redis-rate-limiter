<?php

declare(strict_types=1);

namespace Umair\RateLimiter\Laravel;

use Illuminate\Support\ServiceProvider;
use Umair\RateLimiter\RedisStore;

class RateLimiterServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(RedisStore::class, function ($app) {
            $client = $app['redis']->connection()->client();
            return new RedisStore($client);
        });
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../../config/rate-limiter.php' => config_path('rate-limiter.php'),
            ], 'rate-limiter-config');
        }

        $router = $this->app['router'];
        $router->aliasMiddleware('rate.limit', RateLimitMiddleware::class);
    }
}
