<?php

use Umair\RateLimiter\Algorithms\TokenBucket;
use Umair\RateLimiter\InMemoryStore;

test('initial burst up to capacity is allowed', function () {
    $limiter = new TokenBucket(new InMemoryStore(), capacity: 5, refillPerSecond: 1.0);

    for ($i = 0; $i < 5; $i++) {
        expect($limiter->hit('k')->allowed)->toBeTrue();
    }
    expect($limiter->hit('k')->allowed)->toBeFalse();
});

test('refill restores capacity over time', function () {
    $limiter = new TokenBucket(new InMemoryStore(), capacity: 2, refillPerSecond: 10.0);

    $limiter->hit('k');
    $limiter->hit('k');
    usleep(300_000);
    expect($limiter->hit('k')->allowed)->toBeTrue();
});
