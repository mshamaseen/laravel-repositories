<?php

/** @noinspection DuplicatedCode */
/** @noinspection PhpMultipleClassDeclarationsInspection */

namespace Shamaseen\Repository\Utility;

use Exception;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Model as EloquentModel;

/**
 * Represent methods needed by the controller to make the CRUD workflow.
 */
interface RepositoryInterface
{
    public function simplePaginate(int $limit = 10, array $criteria = []): Paginator;

    public function paginate(int $limit = 10, array $criteria = []): LengthAwarePaginator;

    public function findOrFail(int $id, array $columns = ['*']): EloquentModel;

    public function create(array $data = []): EloquentModel;

    public function update(int $id, array $data = []): int|bool|EloquentModel;

    /**
     * @throws Exception
     */
    public function delete(int $id = 0): int|bool;
}
