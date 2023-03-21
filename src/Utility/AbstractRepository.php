<?php

/** @noinspection PhpUndefinedMethodInspection */
/** @noinspection DuplicatedCode */
/** @noinspection PhpMultipleClassDeclarationsInspection */

namespace Shamaseen\Repository\Utility;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Support\Facades\App;
use Shamaseen\Repository\Interfaces\CriteriableModelInterface;

/**
 * Class Database.
 *
 * @template TModel of EloquentModel&CriteriableModelInterface
 */
abstract class AbstractRepository implements RepositoryInterface
{
    /** @var TModel */
    public EloquentModel&CriteriableModelInterface $model;

    protected ?string $order = null;

    protected string $direction = 'desc';

    public array $with = [];

    private array $scopes = [];

    public function __construct()
    {
        $this->model = App::make($this->getModelClass());
    }

    /** @return class-string<TModel> */
    abstract public function getModelClass(): string;

    /** @return LengthAwarePaginator<TModel> */
    public function paginate(int $limit = 10, array $criteria = []): LengthAwarePaginator
    {
        $this->injectDefaultCriteria($criteria);

        return $this->getNewBuilderWithScope()
            ->with($this->with)
            ->orderByCriteria($criteria)
            ->searchByCriteria($criteria)
            ->filterByCriteria($criteria)
            ->paginate($limit);
    }

    /** @return Paginator<TModel> */
    public function simplePaginate(int $limit = 10, array $criteria = []): Paginator
    {
        return $this->getNewBuilderWithScope()
            ->with($this->with)
            ->orderByCriteria($criteria)
            ->searchByCriteria($criteria)
            ->filterByCriteria($criteria)
            ->simplePaginate($limit);
    }

    /** @return TModel */
    public function findOrFail(int $id, array $columns = ['*']): EloquentModel
    {
        return $this->getNewBuilderWithScope()
            ->with($this->with)
            ->findOrFail($id, $columns);
    }

    /** @return TModel */
    public function create(array $data = []): EloquentModel
    {
        return $this->getNewBuilderWithScope()->create($data);
    }

    /** @return TModel|int|bool */
    public function update(int $id, array $data = []): int|bool|EloquentModel
    {
        return $this->getNewBuilderWithScope()->where('id', $id)->update($data);
    }

    public function delete(int $id = 0): int|bool
    {
        return $this->getNewBuilderWithScope()->where('id', $id)->delete();
    }

    /**
     * @return Builder<TModel>[]|Collection<int, TModel>
     */
    public function get(array $criteria = [], array $columns = ['*']): Collection|array
    {
        return $this->getNewBuilderWithScope()
            ->with($this->with)
            ->orderByCriteria($criteria)
            ->searchByCriteria($criteria)
            ->filterByCriteria($criteria)
            ->get($columns);
    }

    /** @return ?TModel */
    public function find(int $id, array $columns = ['*']): ?EloquentModel
    {
        return $this->getNewBuilderWithScope()
            ->with($this->with)
            ->find($id, $columns);
    }

    /** @return ?TModel */
    public function first(array $criteria = [], array $columns = ['*']): ?EloquentModel
    {
        return $this->getNewBuilderWithScope()
            ->with($this->with)
            ->orderByCriteria($criteria)
            ->searchByCriteria($criteria)
            ->filterByCriteria($criteria)
            ->first($columns);
    }

    /** @return ?TModel */
    public function last(string $key = 'id', array $criteria = [], array $columns = ['*']): ?EloquentModel
    {
        return $this->getNewBuilderWithScope()
            ->with($this->with)
            ->searchByCriteria($criteria)
            ->filterByCriteria($criteria)
            ->orderBy($key, 'desc')
            ->first($columns);
    }

    public function injectDefaultCriteria(array &$criteria): void
    {
        $criteria['order'] = $criteria['order'] ?? $this->order;
        $criteria['direction'] = $criteria['direction'] ?? $this->direction;
    }

    public function scope(callable $callable): static
    {
        $this->scopes[] = $callable;

        return $this;
    }

    /**
     * @return Builder<TModel>
     */
    public function getNewBuilderWithScope(): Builder
    {
        /** @var Builder<TModel> $newQuery */
        $newQuery = $this->model->newQuery();

        foreach ($this->scopes as $scope) {
            $scope($newQuery);
        }

        $this->scopes = [];

        return $newQuery;
    }
}
