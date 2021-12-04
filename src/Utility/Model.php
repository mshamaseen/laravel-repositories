<?php

namespace Shamaseen\Repository\Utility;

use Eloquent;
use Illuminate\Database\Query\Builder;

/**
 * App\Entities\BaseEntity.
 *
 * @property array $searchable
 * @method static bool|null forceDelete()
 * @method static Builder|Model whereId($value)
 * @method static Builder|Model newModelQuery()
 * @method static Builder|Model newQuery()
 * @method static Builder|Model onlyTrashed()
 * @method static Builder|Model query()
 * @method static bool|null restore()
 * @method static Builder|Model withTrashed()
 * @method static Builder|Model withoutTrashed()
 * @mixin Eloquent
 */
class Model extends Eloquent
{
    public array $searchable = [];
    protected $dates = [
        'created_at',
        'updated_at',
    ];
}
