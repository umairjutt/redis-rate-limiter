<?php

declare(strict_types=1);

namespace Umair\RateLimiter\Algorithms;

use Umair\RateLimiter\Contracts\RateLimiterInterface;
use Umair\RateLimiter\Contracts\StoreInterface;
use Umair\RateLimiter\Result;

final class TokenBucket implements RateLimiterInterface
{
    private const SCRIPT = <<<'LUA'
        -- token_bucket
        local key = KEYS[1]
        local capacity = tonumber(ARGV[1])
        local refill = tonumber(ARGV[2])
        local now = tonumber(ARGV[3])

        local data = redis.call('HMGET', key, 'tokens', 'last')
        local tokens = tonumber(data[1]) or capacity
        local last = tonumber(data[2]) or now

        local elapsed = math.max(0, now - last)
        tokens = math.min(capacity, tokens + elapsed * refill)
        last = now

        if tokens >= 1 then
            tokens = tokens - 1
            redis.call('HMSET', key, 'tokens', tokens, 'last', last)
            redis.call('EXPIRE', key, math.ceil(capacity / refill) + 1)
            return {1, math.floor(tokens)}
        end

        local retry = math.ceil((1 - tokens) / refill)
        redis.call('HMSET', key, 'tokens', tokens, 'last', last)
        redis.call('EXPIRE', key, math.ceil(capacity / refill) + 1)
        return {0, math.max(retry, 1)}
    LUA;

    public function __construct(
        private readonly StoreInterface $store,
        private readonly int $capacity,
        private readonly float $refillPerSecond,
        private readonly string $prefix = 'rl:tb:',
    ) {}

    public function hit(string $key): Result
    {
        $now = microtime(true);
        [$ok, $info] = $this->store->eval(
            self::SCRIPT,
            [$this->prefix . $key],
            [$this->capacity, $this->refillPerSecond, $now],
        );

        return $ok === 1
            ? Result::allowed((int) $info, $this->capacity)
            : Result::denied((int) $info, $this->capacity);
    }

    public function reset(string $key): void
    {
        $this->store->delete($this->prefix . $key);
    }
}
