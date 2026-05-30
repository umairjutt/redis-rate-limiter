<?php

declare(strict_types=1);

namespace Umair\RateLimiter;

final class Result
{
    public function __construct(
        public readonly bool $allowed,
        public readonly int $remaining,
        public readonly int $retryAfter,
        public readonly int $limit,
    ) {}

    public static function allowed(int $remaining, int $limit): self
    {
        return new self(true, $remaining, 0, $limit);
    }

    public static function denied(int $retryAfter, int $limit): self
    {
        return new self(false, 0, $retryAfter, $limit);
    }
}
