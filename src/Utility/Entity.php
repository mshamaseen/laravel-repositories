<?php

namespace Shamaseen\Repository\Utility;

use Eloquent;
use Illuminate\Database\Query\Builder;

/**
 * App\Entities\BaseEntity.
 *
 * @property array $searchable
 * @method static bool|null forceDelete()
 * @method static Builder|Entity whereId($value)
 * @method static Builder|Entity newModelQuery()
 * @method static Builder|Entity newQuery()
 * @method static Builder|Entity onlyTrashed()
 * @method static Builder|Entity query()
 * @method static bool|null restore()
 * @method static Builder|Entity withTrashed()
 * @method static Builder|Entity withoutTrashed()
 * @mixin Eloquent
 */
class Entity extends Eloquent
{
    public array $searchable = [];
    protected $dates = [
        'created_at',
        'updated_at',
    ];
}
