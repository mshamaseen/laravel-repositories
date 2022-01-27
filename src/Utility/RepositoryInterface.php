<?php
/** @noinspection DuplicatedCode */
/** @noinspection PhpMultipleClassDeclarationsInspection */

namespace Shamaseen\Repository\Utility;

use Exception;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Model as EloquentModel;

/**
 * Represent methods needed by the controller to make the CRUD workflow
 */
interface RepositoryInterface
{
    /**
     * @param int $limit
     * @param array $criteria
     *
     * @return Paginator
     */
    public function simplePaginate(int $limit = 10, array $criteria = []): Paginator;

    /**
     * @param int $limit
     * @param array $criteria
     *
     * @return LengthAwarePaginator
     */
    public function paginate(int $limit = 10, array $criteria = []): LengthAwarePaginator;

    /**
     * @param int $id
     * @param array $columns
     *
     * @return Model|null
     */
    public function findOrFail(int $id, array $columns = ['*']): ?EloquentModel;

    /**
     * @param array $data
     *
     * @return EloquentModel|null
     */
    public function create(array $data = []): ?EloquentModel;

    /**
     *
     * @param int $id
     * @param array $data
     *
     */
    public function update(int $id, array $data = []): int | bool | EloquentModel;

    /**
     * @param int $id
     *
     * @throws Exception
     */
    public function delete(int $id = 0): int | bool;
}
