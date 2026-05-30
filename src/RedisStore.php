<?php

declare(strict_types=1);

namespace Umair\RateLimiter;

use Umair\RateLimiter\Contracts\StoreInterface;

/**
 * Adapter for both ext-redis (\Redis) and Predis (\Predis\Client).
 */
final class RedisStore implements StoreInterface
{
    public function __construct(private readonly object $client) {}

    public function eval(string $script, array $keys, array $args)
    {
        if ($this->client instanceof \Redis) {
            return $this->client->eval($script, [...$keys, ...$args], count($keys));
        }

        if (method_exists($this->client, 'eval')) {
            return $this->client->eval($script, count($keys), ...$keys, ...$args);
        }

        throw new \RuntimeException('Unsupported Redis client.');
    }

    public function delete(string $key): void
    {
        if ($this->client instanceof \Redis) {
            $this->client->del($key);
            return;
        }

        if (method_exists($this->client, 'del')) {
            $this->client->del([$key]);
        }
    }
}
