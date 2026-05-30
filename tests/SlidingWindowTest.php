<?php

use Umair\RateLimiter\Algorithms\SlidingWindow;
use Umair\RateLimiter\InMemoryStore;

test('allows up to max requests then denies', function () {
    $store = new InMemoryStore();
    $limiter = new SlidingWindow($store, maxRequests: 3, windowSeconds: 10);

    expect($limiter->hit('u1')->allowed)->toBeTrue();
    expect($limiter->hit('u1')->allowed)->toBeTrue();
    expect($limiter->hit('u1')->allowed)->toBeTrue();
    expect($limiter->hit('u1')->allowed)->toBeFalse();
});

test('returns retry-after when denied', function () {
    $store = new InMemoryStore();
    $limiter = new SlidingWindow($store, 1, 60);

    $limiter->hit('u');
    $result = $limiter->hit('u');

    expect($result->allowed)->toBeFalse();
    expect($result->retryAfter)->toBeGreaterThan(0);
});

test('different keys are independent', function () {
    $store = new InMemoryStore();
    $limiter = new SlidingWindow($store, 1, 60);

    expect($limiter->hit('a')->allowed)->toBeTrue();
    expect($limiter->hit('b')->allowed)->toBeTrue();
});

test('reset clears state', function () {
    $store = new InMemoryStore();
    $limiter = new SlidingWindow($store, 1, 60);

    $limiter->hit('z');
    $limiter->reset('z');
    expect($limiter->hit('z')->allowed)->toBeTrue();
});
