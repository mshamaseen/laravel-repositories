<?php

namespace Shamaseen\Repository\Utility;

use Illuminate\Foundation\Auth\User as LaravelUser;
use Illuminate\Database\Eloquent\Builder;
use Shamaseen\Repository\Utility\Models\CachePerRequest;
use Shamaseen\Repository\Utility\Models\Criteriable;

/**
 * App\Entities\BaseEntity.
 *
 * @property array $searchable
 *
 * @method static Builder orderByCriteria()
 * @method static Builder searchByCriteria()
 * @method static Builder filterByCriteria()
 * @method static Builder whereId($value)
 * @method static Builder newModelQuery()
 * @method static Builder newQuery()
 * @method static Builder query()
 * @mixin LaravelUser
 */
class Authenticatable extends LaravelUser
{
    use Criteriable;
    use CachePerRequest;

    protected $dates = [
        'created_at',
        'updated_at',
    ];
}
