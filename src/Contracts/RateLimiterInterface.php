<?php

declare(strict_types=1);

namespace Umair\RateLimiter\Contracts;

use Umair\RateLimiter\Result;

interface RateLimiterInterface
{
    public function hit(string $key): Result;

    public function reset(string $key): void;
}
