<?php

namespace Shamaseen\Repository\Utility\Models;

/**
 * A named bucket inside the per-request cache.
 *
 * Deliberately holds nothing but its key: the entries live in the scoped
 * `RequestCacheStore` and are resolved on every call. A model instance that
 * outlives a request (or a job) therefore cannot drag a stale bucket along with
 * it -- it simply reads whatever store the current scope owns.
 */
class RequestCache
{
    public string $key;

    public function __construct($key)
    {
        $this->key = $key;
    }

    private function store(): RequestCacheStore
    {
        return RequestCacheStore::resolve();
    }

    public function get($key, $default = null)
    {
        return $this->store()->get($this->key, $key, $default);
    }

    public function set($key, $value): bool
    {
        $this->store()->put($this->key, $key, $value);

        return true;
    }

    public function delete(array|string|int $key): bool
    {
        $this->store()->forget($this->key, $key);

        return true;
    }

    /**
     * Empty this bucket. Other buckets -- models that override
     * `getRequestCacheKey()` -- are left alone.
     */
    public function clear(): bool
    {
        $this->store()->flushBucket($this->key);

        return true;
    }

    public function all(): array
    {
        return $this->store()->bucket($this->key);
    }
}
