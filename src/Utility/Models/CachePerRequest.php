<?php

namespace Shamaseen\Repository\Utility\Models;

use Illuminate\Database\Eloquent\Builder;
use Psr\SimpleCache\InvalidArgumentException;

/**
 * @method Builder disableCache()
 * @method Builder enableCache()
 * @method Builder clearCache()
 */
trait CachePerRequest
{
    public bool $requestCacheEnabled = true;
    public RequestCache $requestCache;

    public function __construct(array $attributes = [])
    {
        $this->requestCache = new RequestCache($this->getRequestCacheKey());
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

    /**
     * @throws InvalidArgumentException
     */
    public function scopeClearCache(Builder $query): Builder
    {
        $this->requestCache->clear();

        return $query;
    }

    public function getRequestCacheKey(): string
    {
        return 'repository-cache';
    }

    public function getConnection(): ConnectionProxy
    {
        return new ConnectionProxy(static::resolveConnection($this->getConnectionName()), $this);
    }

    public function refresh(): self
    {
        $wasEnabled = $this->requestCacheEnabled;

        $this->disableCache();

        $result = parent::refresh();

        if ($wasEnabled) {
            $this->enableCache();
        }

        return $result;
    }
}
