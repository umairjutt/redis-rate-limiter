<?php

declare(strict_types=1);

namespace Umair\RateLimiter;

use Umair\RateLimiter\Contracts\StoreInterface;

/**
 * Simple in-memory store used for tests. Implements just enough of the
 * Lua semantics for SlidingWindow and TokenBucket to be testable without
 * a live Redis instance.
 */
final class InMemoryStore implements StoreInterface
{
    /** @var array<string, array<int, float>> */
    private array $zset = [];

    /** @var array<string, array{tokens: float, last: float}> */
    private array $bucket = [];

    public function eval(string $script, array $keys, array $args)
    {
        if (str_contains($script, 'sliding_window')) {
            return $this->slidingWindow($keys[0], (int) $args[0], (int) $args[1], (float) $args[2]);
        }
        if (str_contains($script, 'token_bucket')) {
            return $this->tokenBucket($keys[0], (int) $args[0], (float) $args[1], (float) $args[2]);
        }
        throw new \RuntimeException('Unknown script');
    }

    public function delete(string $key): void
    {
        unset($this->zset[$key], $this->bucket[$key]);
    }

    private function slidingWindow(string $key, int $max, int $window, float $now): array
    {
        $cutoff = $now - $window;
        $this->zset[$key] = array_filter($this->zset[$key] ?? [], fn ($t) => $t > $cutoff);
        $count = count($this->zset[$key]);
        if ($count >= $max) {
            $oldest = min($this->zset[$key]);
            return [0, (int) ceil($oldest + $window - $now)];
        }
        $this->zset[$key][] = $now;
        return [1, $max - $count - 1];
    }

    private function tokenBucket(string $key, int $capacity, float $refillPerSec, float $now): array
    {
        $state = $this->bucket[$key] ?? ['tokens' => (float) $capacity, 'last' => $now];
        $elapsed = $now - $state['last'];
        $state['tokens'] = min((float) $capacity, $state['tokens'] + $elapsed * $refillPerSec);
        $state['last'] = $now;

        if ($state['tokens'] >= 1.0) {
            $state['tokens'] -= 1.0;
            $this->bucket[$key] = $state;
            return [1, (int) floor($state['tokens'])];
        }

        $retry = (int) ceil((1.0 - $state['tokens']) / $refillPerSec);
        $this->bucket[$key] = $state;
        return [0, max($retry, 1)];
    }
}
