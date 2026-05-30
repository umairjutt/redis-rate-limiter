<?php

declare(strict_types=1);

namespace Umair\RateLimiter\Algorithms;

use Umair\RateLimiter\Contracts\RateLimiterInterface;
use Umair\RateLimiter\Contracts\StoreInterface;
use Umair\RateLimiter\Result;

final class SlidingWindow implements RateLimiterInterface
{
    private const SCRIPT = <<<'LUA'
        -- sliding_window
        local key = KEYS[1]
        local max = tonumber(ARGV[1])
        local window = tonumber(ARGV[2])
        local now = tonumber(ARGV[3])
        local cutoff = now - window

        redis.call('ZREMRANGEBYSCORE', key, '-inf', cutoff)
        local count = tonumber(redis.call('ZCARD', key))

        if count >= max then
            local oldest = tonumber(redis.call('ZRANGE', key, 0, 0, 'WITHSCORES')[2])
            local retry = math.ceil(oldest + window - now)
            return {0, retry}
        end

        redis.call('ZADD', key, now, now .. ':' .. math.random())
        redis.call('EXPIRE', key, window)
        return {1, max - count - 1}
    LUA;

    public function __construct(
        private readonly StoreInterface $store,
        private readonly int $maxRequests,
        private readonly int $windowSeconds,
        private readonly string $prefix = 'rl:sw:',
    ) {}

    public function hit(string $key): Result
    {
        $now = microtime(true);
        [$ok, $info] = $this->store->eval(
            self::SCRIPT,
            [$this->prefix . $key],
            [$this->maxRequests, $this->windowSeconds, $now],
        );

        return $ok === 1
            ? Result::allowed((int) $info, $this->maxRequests)
            : Result::denied((int) $info, $this->maxRequests);
    }

    public function reset(string $key): void
    {
        $this->store->delete($this->prefix . $key);
    }
}
