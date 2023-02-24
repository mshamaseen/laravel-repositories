<?php

namespace Shamaseen\Repository\Utility\Models;

use Illuminate\Database\Eloquent\Builder;

/**
 * @method static Builder filterByCriteria(array $criteria)
 * @method static Builder searchByCriteria(array $criteria)
 * @method static Builder orderByCriteria(array $criteria)
 * @method static Builder setSearchables(array $searchables)
 * @method static Builder appendSearchables(array $searchables)
 * @method static Builder setFilterables(array $filterables)
 * @method static Builder appendFilterables(array $filterables)
 * @method static Builder setSortables(array $sortables)
 * @method static Builder appendSortables(array $sortables)
 */
trait Criteriable
{
    protected ?array $searchables = null;
    protected ?array $filterables = null;
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

    public function scopeAppendSearchables($query, array $searchables): Builder
    {
        $this->searchables = collect($this->searchables)->merge($searchables)->unique()->toArray();

        return $query;
    }

    public function scopeSetSearchables($query, array $searchables): Builder
    {
        $this->searchables = $searchables;

        return $query;
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

    public function scopeAppendSortables($query, array $sortables): Builder
    {
        $this->sortables = collect($this->sortables)->merge($sortables)->unique()->toArray();

        return $query;
    }

    public function scopeSetSortables($query, array $sortables): Builder
    {
        $this->sortables = $sortables;

        return $query;
    }

    /**
     * By default, all fillables and not hidden are filterables, if you want to override that explicitly set an array of searchables.
     * For a relation this is the syntax [ relationName => [columns in the relation] ].
     */
    public function getFilterables(): array
    {
        if (null !== $this->filterables) {
            return $this->filterables;
        }

        return array_diff($this->getFillable(), $this->getHidden());
    }

    public function scopeAppendFilterables($query, array $filterables): Builder
    {
        $this->filterables = collect($this->filterables)->merge($filterables)->unique()->toArray();

        return $query;
    }

    public function scopeSetFilterables($query, array $filterables): Builder
    {
        $this->filterables = $filterables;

        return $query;
    }
}
