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
     * @throws InvalidArgumentException
     */
    public function cacheOrNext($fullQuery, callable $next)
    {
        if(config('repository.disable_cache')) {
            return $next();
        }

        if ($this->model->requestCacheEnabled) {
            $cache = $this->model->requestCache;

            // if the key exist then return the cached version
            if ($fromCache = $cache->get($fullQuery)) {
                return $fromCache;
            }

            $result = $next();

            $cache->set($fullQuery, $result);

            return $result;
        }

        return $next();
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
        });
    }

    /**
     * @throws InvalidArgumentException
     */
    public function select($query, $bindings = [], $useReadPdo = true)
    {
        $fullQuery = Str::replaceArray('?', $bindings, $query);

        return $this->cacheOrNext($fullQuery, function () use ($query, $bindings, $useReadPdo) {
            return $this->realConnection->select($query, $bindings, $useReadPdo);
        });
    }

    public function table($table, $as = null)
    {
        return $this->realConnection->table($table, $as);
    }

    public function raw($value)
    {
        return $this->realConnection->raw($value);
    }

    public function cursor($query, $bindings = [], $useReadPdo = true)
    {
        return $this->realConnection->cursor($query, $bindings, $useReadPdo);
    }

    public function insert($query, $bindings = [])
    {
        return $this->realConnection->insert($query, $bindings);
    }

    public function update($query, $bindings = [])
    {
        return $this->realConnection->update($query, $bindings);
    }

    public function delete($query, $bindings = [])
    {
        return $this->realConnection->delete($query, $bindings);
    }

    public function statement($query, $bindings = [])
    {
        return $this->realConnection->statement($query, $bindings);
    }

    public function affectingStatement($query, $bindings = [])
    {
        return $this->realConnection->affectingStatement($query, $bindings);
    }

    public function unprepared($query)
    {
        return $this->realConnection->unprepared($query);
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

    public function scalar($query, $bindings = [], $useReadPdo = true)
    {
        return $this->scalar($query, $bindings, $useReadPdo);
    }
}
