# Redis Rate Limiter (PHP)

[CI results](https://github.com/umairjutt/redis-rate-limiter/actions) · [Engineering guide](PORTFOLIO.md)

> **Status:** Development library. Registry publication is not currently verified. Existing algorithm tests use an in-memory store; real Redis adapter and concurrency verification remain.

A framework-agnostic PHP rate limiter with a Laravel middleware bridge. Implements both **sliding window** and **token bucket** algorithms backed by Redis Lua scripts for atomic decisions.

[![License](https://img.shields.io/badge/license-MIT-green)](LICENSE)

## What this demonstrates

- **Two algorithms** behind a single interface — `SlidingWindow` and `TokenBucket`
- **Atomic Redis operations** via Lua scripts (atomic within the script)
- **Framework-agnostic core** — works with any PHP 8.2+ project
- **Laravel middleware** wrapper for drop-in API protection
- **Typed PHP core** — Redis access is delegated through a store interface
- **Composer package** publishable to Packagist

## Develop from source

Registry installation is not currently verified. Use a local source checkout:

```bash
git clone https://github.com/umairjutt/redis-rate-limiter.git
cd redis-rate-limiter
composer install
./vendor/bin/pest
```

Examples below use this checkout. A consuming application needs an explicit local Composer path repository until a registry release is available.

## Usage — vanilla PHP

```php
use Umair\RateLimiter\Algorithms\SlidingWindow;
use Umair\RateLimiter\RedisStore;

$redis = new \Redis();
$redis->connect('127.0.0.1', 6379);

$limiter = new SlidingWindow(
    store: new RedisStore($redis),
    maxRequests: 100,
    windowSeconds: 60,
);

$result = $limiter->hit("user:42");

if (!$result->allowed) {
    http_response_code(429);
    header("Retry-After: {$result->retryAfter}");
    exit('Rate limit exceeded');
}
```

## Usage — Laravel

After configuring the local package and service provider in a Laravel development application, add to a route:

```php
Route::middleware('rate.limit:api,100,60')->get('/users', $controller);
// algorithm=sliding, max=100, window=60s
```

Or globally in `bootstrap/app.php`:

```php
$middleware->alias([
    'rate.limit' => \Umair\RateLimiter\Laravel\RateLimitMiddleware::class,
]);
```

## Algorithms

### Sliding Window (`SlidingWindow`)
Counts hits in a rolling N-second window using a Redis sorted set. Entries remain for the configured window; memory use depends on request volume.

### Token Bucket (`TokenBucket`)
Refills tokens at a fixed rate. Allows short bursts up to the bucket capacity. Best for "smooth-but-bursty" workloads.

## Performance

Each algorithm makes one Redis Lua call per decision. No reproducible benchmark is included; throughput and latency need a documented workload, client and Redis configuration.

## Tests

```bash
composer install
./vendor/bin/pest
```

## License

MIT

<!-- ownership:author -->
---

## Author

**Umair** &mdash; [@umairjutt](https://github.com/umairjutt)

Maintained by [Umair Jutt](https://github.com/umairjutt). See [LICENSE](LICENSE) for repository licensing.
