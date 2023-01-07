<?php

namespace Shamaseen\Repository\Utility\Models;

use Illuminate\Database\Eloquent\Builder;

trait Criteriable
{
    protected ?array $searchables = null;
    protected ?array $filterable = null;
    protected ?array $sortables = null;

    /**
     * @var array<array<string>>
     */
    protected ?array $fulltextSearch = [];

    public function scopeFilterByCriteria($query, array $criteria): Builder
    {
        foreach ($this->getFilterables() as $method => $columns) {
            // if this is associative then it is a relation
            if ('string' === gettype($method)) {
                if (method_exists($this, $method) && array_key_exists($method, $criteria)) {
                    $query->whereHas($method, function ($query) use ($criteria, $columns, $method) {
                        /* @var $query Builder */
                        $query->where(function ($query2) use ($criteria, $columns, $method) {
                            /* @var $query2 Builder */
                            foreach ((array) $columns as $column) {
                                $query2->where($column, $criteria[$method]);
                            }
                        });
                    });
                }
            } elseif (array_key_exists($columns, $criteria)) {
                $query->where($columns, $criteria[$columns]);
            }
        }

        return $query;
    }

    public function scopeSearchByCriteria($query, array $criteria): Builder
    {
        if (isset($criteria['search'])) {
            $query->where(function ($q) use ($criteria) {
                foreach ((array) $this->fulltextSearch as $pair) {
                    $q->orWhereRaw(sprintf("match(%s) against('%s')",
                        implode(',', $pair), $criteria['search']));
                }

                /*
                 * @var Builder $q
                 */
                foreach ($this->getSearchables() as $method => $columns) {
                    if (method_exists($this, $method)) {
                        $q->orWhereHas($method, function ($query) use ($criteria, $columns) {
                            /* @var $query Builder */
                            $query->where(function ($query2) use ($criteria, $columns) {
                                /* @var $query2 Builder */
                                foreach ((array) $columns as $column) {
                                    $query2->orWhere($column, 'like', $criteria['search'].'%');
                                }
                            });
                        });
                    } else {
                        $q->orWhere($columns, 'like', $criteria['search'].'%');
                    }
                }
            });
        }

        return $query;
    }

    public function scopeOrderByCriteria($query, array $criteria): Builder
    {
        if (isset($criteria['order']) && in_array($criteria['order'], $this->getSortables())) {
            $query->orderBy($criteria['order'], $criteria['direction'] ?? 'desc');
        }

        return $query;
    }

    /**
     * By default, all fillables and not hidden are searchables, if you want to override that explicitly set an array of searchables.
     * For a relation this is the syntax [ relationName => [columns in the relation] ].
     */
    public function getSearchables(): array
    {
        if (null !== $this->searchables) {
            return $this->searchables;
        }

        return array_diff($this->getFillable(), $this->getHidden());
    }

    /**
     * By default, all fillables and not hidden are sortables, if you want to override that explicitly set an array of sortables.
     */
    public function getSortables(): array
    {
        if (null !== $this->sortables) {
            return $this->sortables;
        }

        return array_diff($this->getFillable(), $this->getHidden());
    }

    public function setSearchables($searchables): self
    {
        $this->searchables = $searchables;

        return $this;
    }

    /**
     * By default, all fillables and not hidden are filterables, if you want to override that explicitly set an array of searchables.
     * For a relation this is the syntax [ relationName => [columns in the relation] ].
     */
    public function getFilterables(): array
    {
        if (null !== $this->filterable) {
            return $this->filterable;
        }

        return array_diff($this->getFillable(), $this->getHidden());
    }

    public function setFilterables($searchables): self
    {
        $this->searchables = $searchables;

        return $this;
    }
}
