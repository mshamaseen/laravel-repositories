<?php

namespace Shamaseen\Repository\Utility\Models;

use Illuminate\Database\Eloquent\Builder;
use Psr\SimpleCache\InvalidArgumentException;

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

    public function getConnection()
    {
        return new ConnectionProxy(static::resolveConnection($this->getConnectionName()), $this);
    }
}
