<?php

declare(strict_types=1);

namespace Umair\RateLimiter\Laravel;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Umair\RateLimiter\Algorithms\SlidingWindow;
use Umair\RateLimiter\Algorithms\TokenBucket;
use Umair\RateLimiter\Contracts\RateLimiterInterface;
use Umair\RateLimiter\RedisStore;

/**
 * Usage: ->middleware('rate.limit:scope,max,window[,algo]')
 *   scope: bucket identifier (e.g. "api", "login")
 *   max: requests per window (sliding) or capacity (token)
 *   window: seconds (sliding) or refill divisor (token)
 *   algo: "sliding" (default) | "token"
 */
class RateLimitMiddleware
{
    public function __construct(private readonly RedisStore $store) {}

    public function handle(Request $request, Closure $next, string $scope = 'api', string $max = '60', string $window = '60', string $algo = 'sliding'): Response
    {
        $limiter = $this->makeLimiter($algo, (int) $max, (float) $window);
        $key = "{$scope}:" . ($request->user()?->getAuthIdentifier() ?? $request->ip());

        $result = $limiter->hit($key);

        if (!$result->allowed) {
            return response()->json([
                'error' => 'Too Many Requests',
                'retry_after' => $result->retryAfter,
            ], 429, [
                'Retry-After' => (string) $result->retryAfter,
                'X-RateLimit-Limit' => (string) $result->limit,
                'X-RateLimit-Remaining' => '0',
            ]);
        }

        $response = $next($request);
        $response->headers->set('X-RateLimit-Limit', (string) $result->limit);
        $response->headers->set('X-RateLimit-Remaining', (string) $result->remaining);
        return $response;
    }

    private function makeLimiter(string $algo, int $max, float $window): RateLimiterInterface
    {
        return $algo === 'token'
            ? new TokenBucket($this->store, $max, $max / $window)
            : new SlidingWindow($this->store, $max, (int) $window);
    }
}
