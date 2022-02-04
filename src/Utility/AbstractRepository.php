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

/**
 * Class Database.
 */
abstract class AbstractRepository implements RepositoryInterface
{
    protected EloquentModel $model;

    protected ?string $order = null;

    protected string $direction = 'desc';

    protected array $with = [];

    private array $scopes = [];

    public function __construct()
    {
        $this->model = App::make($this->getModelClass());
    }

    abstract protected function getModelClass(): string;

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

    public function simplePaginate(int $limit = 10, array $criteria = []): Paginator
    {
        return $this->getNewBuilderWithScope()
            ->with($this->with)
            ->orderByCriteria($criteria)
            ->searchByCriteria($criteria)
            ->filterByCriteria($criteria)
            ->simplePaginate($limit);
    }

    public function findOrFail(int $id, array $columns = ['*']): EloquentModel
    {
        return $this->getNewBuilderWithScope()
            ->with($this->with)
            ->findOrFail($id, $columns);
    }

    public function create(array $data = []): EloquentModel
    {
        return $this->getNewBuilderWithScope()->create($data);
    }

    public function update(int $id, array $data = []): int|bool|EloquentModel
    {
        return $this->getNewBuilderWithScope()->where('id', $id)->update($data);
    }

    public function delete(int $id = 0): int|bool
    {
        return $this->getNewBuilderWithScope()->where('id', $id)->delete();
    }

    /**
     * @return Builder[]|Collection
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

    public function find(int $id, array $columns = ['*']): ?EloquentModel
    {
        return $this->getNewBuilderWithScope()
            ->with($this->with)
            ->find($id, $columns);
    }

    public function first(array $criteria = [], array $columns = ['*']): ?Model
    {
        return $this->getNewBuilderWithScope()
            ->with($this->with)
            ->orderByCriteria($criteria)
            ->searchByCriteria($criteria)
            ->filterByCriteria($criteria)
            ->first($columns);
    }

    public function last(string $key = 'id', array $criteria = [], array $columns = ['*']): ?Model
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

    private function getNewBuilderWithScope(): Builder
    {
        $newQuery = $this->model->newQuery();

        foreach ($this->scopes as $scope) {
            $scope($newQuery);
        }

        $this->scopes = [];

        return $newQuery;
    }
}
