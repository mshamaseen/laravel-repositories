<?php

namespace Shamaseen\Repository\Utility;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\User as LaravelUser;
use Shamaseen\Repository\Interfaces\CriteriableModelInterface;
use Shamaseen\Repository\Utility\Models\CachePerRequest;
use Shamaseen\Repository\Utility\Models\Criteriable;

/**
 * App\Entities\BaseEntity.
 *
 * @property array $searchables
 *
 * @method static Builder whereId($value)
 * @method static Builder newModelQuery()
 * @method static Builder newQuery()
 * @method static Builder query()
 *
 * @extends LaravelUser
 */
class Authenticatable extends LaravelUser implements CriteriableModelInterface
{
    use Criteriable;
    use CachePerRequest;

    protected $dates = [
        'created_at',
        'updated_at',
    ];
}
