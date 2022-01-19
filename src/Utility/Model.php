<?php

namespace Shamaseen\Repository\Utility\Models;

use Eloquent;
use \Illuminate\Database\Eloquent\Builder;

/**
 * App\Entities\BaseEntity.
 *
 * @property array $searchable
 * @method static bool|null forceDelete()
 * @method static Builder whereId($value)
 * @method static Builder newModelQuery()
 * @method static Builder newQuery()
 * @method static Builder onlyTrashed()
 * @method static Builder query()
 * @method static bool|null restore()
 * @method static Builder withTrashed()
 * @method static Builder withoutTrashed()
 * @mixin Eloquent
 */
class Model extends Eloquent
{
    use Criteriable;

    protected $dates = [
        'created_at',
        'updated_at',
    ];
}
