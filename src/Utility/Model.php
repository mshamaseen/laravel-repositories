<?php

namespace Shamaseen\Repository\Utility;

use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Shamaseen\Repository\Utility\Models\CachePerRequest;
use Shamaseen\Repository\Utility\Models\Criteriable;

/**
 * App\Entities\BaseEntity.
 *
 * @property array $searchable
 *
 * @method static Builder orderByCriteria($criteria)
 * @method static Builder searchByCriteria($criteria)
 * @method static Builder filterByCriteria($criteria)
 * @method static Builder whereId($value)
 * @method static Builder newModelQuery()
 * @method static Builder newQuery()
 * @method static Builder query()
 * @mixin Eloquent
 */
class Model extends Eloquent
{
    use Criteriable;
    use CachePerRequest;

    protected $dates = [
        'created_at',
        'updated_at',
    ];
}
