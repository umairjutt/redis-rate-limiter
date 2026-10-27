# Engineering guide

PHP sliding-window and token-bucket rate limiter with Redis Lua implementations and a Laravel middleware adapter.

## Code tour

- [src/Algorithms/SlidingWindow.php](src/Algorithms/SlidingWindow.php)
- [src/Algorithms/TokenBucket.php](src/Algorithms/TokenBucket.php)
- [src/RedisStore.php](src/RedisStore.php)
- [tests](tests)

## Scope and verification

Development library. Registry publication is not currently verified. Existing algorithm tests use an in-memory store; real Redis adapter and concurrency verification remain.

[Current CI runs](https://github.com/umairjutt/redis-rate-limiter/actions). A workflow definition or build result does not establish that all runtime scenarios pass. The README contains development commands; unfinished setup and behavior need completion before production deployment.

## Maintainer

[Umair Jutt](https://github.com/umairjutt). Frameworks and dependencies retain their own ownership and licensing.
