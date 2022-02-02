<?php

namespace Shamaseen\Repository\Utility\Models;

use Cache;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Psr\SimpleCache\InvalidArgumentException;

trait CachePerRequest
{
    public static Collection $requestCache;

    protected bool $requestCacheEnabled = true;
    protected array $methodsToCache = [
        'find',
        'first',
        'firstWhere',
        'findOrFail',
        'firstOrCreate',
        'firstOrFail',
        'sole',
        'valueOrFail',
    ];

    public function __construct(array $attributes = [])
    {
        static::$requestCache = collect([]);
        parent::__construct($attributes);
    }

    public function scopeDisableCache(Builder $query): Builder
    {
        $this->requestCacheEnabled = false;

        return $query;
    }

    public function scopeEnableCache(Builder $query): Builder
    {
        $this->requestCacheEnabled = true;

        return $query;
    }

    public function scopeClearCache(Builder $query): Builder
    {
        Cache::store('array')->clear();

        return $query;
    }

    /**
     * @throws InvalidArgumentException
     */
    public function __call($method, $parameters)
    {
        if ($this->requestCacheEnabled && in_array($method, $this->methodsToCache)) {
            $requestCacheKey = json_encode([static::class, $method, $parameters]);

            // if the key exist then return the cached version
            if ($fromCache = Cache::store('array')->get($requestCacheKey)) {
                return $fromCache;
            }

            $result = parent::__call($method, $parameters);

            Cache::store('array')->put($requestCacheKey, $result);

            return $result;
        }

        return parent::__call($method, $parameters);
    }
}
