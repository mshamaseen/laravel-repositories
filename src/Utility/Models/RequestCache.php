<?php

namespace Shamaseen\Repository\Utility\Models;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Psr\SimpleCache\InvalidArgumentException;

class RequestCache
{
    public string $key;

    public function __construct($key)
    {
        $this->key = $key;
    }

    /**
     * @throws InvalidArgumentException
     */
    private function getRepositoryCacheCollection(): Collection
    {
        return Cache::store('array')->get($this->key, collect([]));
    }

    /**
     * @throws InvalidArgumentException
     */
    public function get($key, $default = null)
    {
        return $this->getRepositoryCacheCollection()->get($key, $default);
    }

    /**
     * @throws InvalidArgumentException
     */
    public function set($key, $value): bool
    {
        return Cache::store('array')->set(
            $this->key,
            $this->getRepositoryCacheCollection()->put($key, $value)
        );
    }

    /**
     * @throws InvalidArgumentException
     */
    public function delete(array|string|int $key): bool
    {
        return Cache::store('array')->set(
            $this->key,
            $this->getRepositoryCacheCollection()->forget($key)
        );
    }

    /**
     * @throws InvalidArgumentException
     */
    public function clear(): bool
    {
        return Cache::store('array')->set(
            $this->key,
            collect([])
        );
    }

    public function all(): array
    {
        return $this->getRepositoryCacheCollection()->all();
    }
}
