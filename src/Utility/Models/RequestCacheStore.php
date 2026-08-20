<?php

namespace Shamaseen\Repository\Utility\Models;

use Illuminate\Container\Container;

/**
 * Storage behind the per-request query cache.
 *
 * This is registered as a *scoped* container binding rather than a singleton, and
 * that distinction is the whole point of the class. Laravel forgets scoped
 * instances at exactly the boundaries we mean by "per request": between two queue
 * jobs (`Illuminate\Queue\Worker`'s reset closure calls `forgetScopedInstances()`)
 * and between two Octane requests.
 *
 * Keeping the entries in `Cache::store('array')` instead -- as this package used to
 * -- tied them to the `CacheManager` singleton, which nothing ever resets. In a
 * `queue:work` daemon that meant cached rows survived from one job to the next for
 * the life of the worker process: unbounded staleness, and an array that only grew.
 */
class RequestCacheStore
{
    /**
     * Bucket key => (query key => result).
     *
     * Models may override `getRequestCacheKey()` to split themselves into their own
     * bucket, so this is a map of maps rather than a flat list.
     *
     * @var array<string, array<string|int, mixed>>
     */
    private array $buckets = [];

    /**
     * The one place this binding's lifetime is declared. `scopedIf` is a no-op when
     * the binding already exists, so calling it again is free and cannot register a
     * second scope.
     */
    public static function register(Container $container): void
    {
        $container->scopedIf(self::class, static fn () => new self());
    }

    /**
     * Resolve the store for the current scope.
     *
     * Registers on the way through so a model still caches when the service provider
     * has not run -- a bare container, or an app that excluded the provider from
     * package discovery. Without this the container would happily auto-resolve an
     * unbound `RequestCacheStore` fresh on every call, silently turning the cache
     * into a no-op instead of failing loudly.
     */
    public static function resolve(): self
    {
        $container = Container::getInstance();

        self::register($container);

        return $container->make(self::class);
    }

    /**
     * `array_key_exists`, not `isset`: a legitimately cached `null` result has to
     * count as a hit, otherwise every null re-queries the database.
     */
    public function get(string $bucket, string|int $key, mixed $default = null): mixed
    {
        return array_key_exists($key, $this->buckets[$bucket] ?? [])
            ? $this->buckets[$bucket][$key]
            : $default;
    }

    public function put(string $bucket, string|int $key, mixed $value): void
    {
        $this->buckets[$bucket][$key] = $value;
    }

    /**
     * @param array<string|int>|string|int $key
     */
    public function forget(string $bucket, array|string|int $key): void
    {
        foreach ((array) $key as $single) {
            unset($this->buckets[$bucket][$single]);
        }
    }

    public function flushBucket(string $bucket): void
    {
        unset($this->buckets[$bucket]);
    }

    /**
     * Drop every bucket, whatever key it was stored under.
     */
    public function flushAll(): void
    {
        $this->buckets = [];
    }

    /**
     * @return array<string|int, mixed>
     */
    public function bucket(string $bucket): array
    {
        return $this->buckets[$bucket] ?? [];
    }
}
