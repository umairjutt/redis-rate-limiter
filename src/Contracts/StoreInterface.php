<?php

declare(strict_types=1);

namespace Umair\RateLimiter\Contracts;

interface StoreInterface
{
    /**
     * Execute a Lua script atomically.
     *
     * @param  array<int, string|int|float>  $keys
     * @param  array<int, string|int|float>  $args
     * @return mixed
     */
    public function eval(string $script, array $keys, array $args);

    public function delete(string $key): void;
}
