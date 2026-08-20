<?php

namespace Shamaseen\Repository\Utility\Models;

use Closure;
use Illuminate\Database\Connection;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Eloquent\Model as LaravelModel;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Str;
use Psr\SimpleCache\InvalidArgumentException;
use Shamaseen\Repository\Utility\Model;

class ConnectionProxy implements ConnectionInterface
{
    public Connection $realConnection;

    public function __construct(Connection $realConnection, private readonly Model|LaravelModel $model)
    {
        $this->realConnection = $realConnection;
    }

    // Proxy methods >>>

    /**
     * SQL fragments meaning the caller asked the database for a row lock.
     * Serving such a read from cache would silently skip the lock.
     */
    private const array LOCKING_PATTERNS = [
        '/\\bfor\\s+update\\b/i',              // MySQL, Postgres
        '/\\bfor\\s+no\\s+key\\s+update\\b/i',  // Postgres
        '/\\bfor\\s+share\\b/i',               // MySQL 8, Postgres
        '/\\bfor\\s+key\\s+share\\b/i',        // Postgres
        '/\\block\\s+in\\s+share\\s+mode\\b/i', // MySQL 5.7
        '/\\b(?:updlock|holdlock|rowlock)\\b/i',  // SQL Server table hints
    ];

    /**
     * Decide whether a read may be served from, or stored in, the request cache.
     *
     * Takes the raw query (bindings still as `?`) so that a binding *value* can
     * never be mistaken for a locking clause.
     */
    private function shouldCache(string $query): bool
    {
        if (config('repository.disable_cache')) {
            return false;
        }

        if (!$this->model->requestCacheEnabled) {
            return false;
        }

        // A read cached inside a transaction would outlive a rollback, leaving the
        // cache asserting a value that was never committed.
        if ($this->realConnection->transactionLevel() > 0) {
            return false;
        }

        // A locking read served from cache never reaches the database, so the lock
        // is never taken. Always let these through.
        return !$this->queryTakesLock($query);
    }

    private function queryTakesLock(string $query): bool
    {
        foreach (self::LOCKING_PATTERNS as $pattern) {
            if (preg_match($pattern, $query)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @throws InvalidArgumentException
     */
    public function cacheOrNext($fullQuery, callable $next, ?string $rawQuery = null)
    {
        if (!$this->shouldCache($rawQuery ?? $fullQuery)) {
            return $next();
        }

        $cache = $this->model->requestCache;
        $miss = new \stdClass();

        // if the key exists then return the cached version; compared against a
        // sentinel so a legitimately empty or null result still counts as a hit.
        $fromCache = $cache->get($fullQuery, $miss);

        if ($miss !== $fromCache) {
            return $fromCache;
        }

        $result = $next();

        $cache->set($fullQuery, $result);

        return $result;
    }

    /**
     * Run a write, then drop every cached read.
     *
     * Invalidation is deliberately coarse. The cache is a single bucket keyed by
     * SQL string, so there is no reliable way to tell which entries a given write
     * touched; clearing all of them is the only conservative choice. Writes that
     * bypass this proxy (raw `DB::table()` calls, for instance) cannot invalidate
     * anything -- see the caveats in docs/Base/Model.md.
     *
     * @throws InvalidArgumentException
     */
    private function writeAndInvalidate(callable $write)
    {
        try {
            return $write();
        } finally {
            if (!config('repository.disable_cache')) {
                $this->model->requestCache->clear();
            }
        }
    }

    // Laravel methods >>>

    /**
     * @throws InvalidArgumentException
     */
    public function selectOne($query, $bindings = [], $useReadPdo = true)
    {
        $fullQuery = Str::replaceArray('?', $bindings, $query);

        return $this->cacheOrNext($fullQuery, function () use ($query, $bindings, $useReadPdo) {
            return $this->realConnection->selectOne($query, $bindings, $useReadPdo);
        }, $query);
    }

    /**
     * @param string $query
     * @param array $bindings
     * @param true $useReadPdo
     * @param array $fetchUsing
     * @throws InvalidArgumentException
     */
    public function select($query, $bindings = [], $useReadPdo = true, array $fetchUsing = [])
    {
        $fullQuery = Str::replaceArray('?', $bindings, $query);

        return $this->cacheOrNext($fullQuery, function () use ($query, $bindings, $useReadPdo, $fetchUsing) {
            return $this->realConnection->select($query, $bindings, $useReadPdo, $fetchUsing);
        }, $query);
    }

    public function table($table, $as = null)
    {
        return $this->realConnection->table($table, $as);
    }

    public function raw($value)
    {
        return $this->realConnection->raw($value);
    }

    public function cursor($query, $bindings = [], $useReadPdo = true, array $fetchUsing = [])
    {
        return $this->realConnection->cursor($query, $bindings, $useReadPdo, $fetchUsing);
    }

    /**
     * @throws InvalidArgumentException
     */
    public function insert($query, $bindings = [])
    {
        return $this->writeAndInvalidate(function () use ($query, $bindings) {
            return $this->realConnection->insert($query, $bindings);
        });
    }

    /**
     * @throws InvalidArgumentException
     */
    public function update($query, $bindings = [])
    {
        return $this->writeAndInvalidate(function () use ($query, $bindings) {
            return $this->realConnection->update($query, $bindings);
        });
    }

    /**
     * @throws InvalidArgumentException
     */
    public function delete($query, $bindings = [])
    {
        return $this->writeAndInvalidate(function () use ($query, $bindings) {
            return $this->realConnection->delete($query, $bindings);
        });
    }

    /**
     * @throws InvalidArgumentException
     */
    public function statement($query, $bindings = [])
    {
        return $this->writeAndInvalidate(function () use ($query, $bindings) {
            return $this->realConnection->statement($query, $bindings);
        });
    }

    /**
     * @throws InvalidArgumentException
     */
    public function affectingStatement($query, $bindings = [])
    {
        return $this->writeAndInvalidate(function () use ($query, $bindings) {
            return $this->realConnection->affectingStatement($query, $bindings);
        });
    }

    /**
     * @throws InvalidArgumentException
     */
    public function unprepared($query)
    {
        return $this->writeAndInvalidate(function () use ($query) {
            return $this->realConnection->unprepared($query);
        });
    }

    public function prepareBindings(array $bindings)
    {
        return $this->realConnection->prepareBindings($bindings);
    }

    public function transaction(Closure $callback, $attempts = 1)
    {
        return $this->realConnection->transaction($callback, $attempts);
    }

    public function beginTransaction()
    {
        return $this->realConnection->beginTransaction();
    }

    public function commit()
    {
        return $this->realConnection->commit();
    }

    public function rollBack()
    {
        return $this->realConnection->rollBack();
    }

    public function transactionLevel()
    {
        return $this->realConnection->transactionLevel();
    }

    public function pretend(Closure $callback)
    {
        return $this->realConnection->pretend($callback);
    }

    public function getDatabaseName()
    {
        return $this->realConnection->getDatabaseName();
    }

    public function __set(string $name, $value): void
    {
        $this->realConnection->{$name} = $value;
    }

    public function __get(string $name)
    {
        return $this->realConnection->{$name};
    }

    public function __call(string $name, array $arguments)
    {
        return $this->realConnection->{$name}(...$arguments);
    }

    /**
     * Get a new query builder instance.
     *
     * @return QueryBuilder
     */
    public function query()
    {
        return new QueryBuilder(
            // pass our connection instead of laravel one.
            $this,
            $this->realConnection->getQueryGrammar(),
            $this->realConnection->getPostProcessor()
        );
    }

    /**
     * @throws InvalidArgumentException
     */
    public function scalar($query, $bindings = [], $useReadPdo = true)
    {
        $fullQuery = Str::replaceArray('?', $bindings, $query);

        return $this->cacheOrNext($fullQuery, function () use ($query, $bindings, $useReadPdo) {
            return $this->realConnection->scalar($query, $bindings, $useReadPdo);
        }, $query);
    }
}