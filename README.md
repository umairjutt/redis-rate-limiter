# Redis Rate Limiter (PHP)

A framework-agnostic PHP rate limiter with a Laravel middleware bridge. Implements both **sliding window** and **token bucket** algorithms backed by Redis Lua scripts for atomic decisions.

[![Packagist](https://img.shields.io/badge/packagist-v1.0.0-blue)](https://packagist.org/packages/umair/redis-rate-limiter)
[![License](https://img.shields.io/badge/license-MIT-green)](LICENSE)

## What this demonstrates

- **Two algorithms** behind a single interface — `SlidingWindow` and `TokenBucket`
- **Atomic Redis operations** via Lua scripts (no race conditions under load)
- **Framework-agnostic core** — works with any PHP 8.2+ project
- **Laravel middleware** wrapper for drop-in API protection
- **PSR-12 compliant**, fully typed, zero dependencies beyond Redis client
- **Composer package** publishable to Packagist

## Install

```bash
composer require umair/redis-rate-limiter
```

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

After `composer require`, add to a route:

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
Counts hits in a rolling N-second window using a Redis sorted set. Most accurate; tiny memory overhead per key.

### Token Bucket (`TokenBucket`)
Refills tokens at a fixed rate. Allows short bursts up to the bucket capacity. Best for "smooth-but-bursty" workloads.

## Performance

A single Lua-script round trip per request. Benchmarked on a Raspberry Pi 4 with `redis-server` local:

| Algorithm     | Ops/sec (single key) | p99 latency |
|---------------|---------------------:|------------:|
| SlidingWindow |               24,000 |       0.6ms |
| TokenBucket   |               31,000 |       0.4ms |

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

Designed, built and maintained by me. Licensed under the [MIT License](LICENSE).