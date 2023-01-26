<?php

namespace Shamaseen\Repository\Utility;

use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Shamaseen\Repository\Interfaces\CriteriableModelInterface;
use Shamaseen\Repository\Utility\Models\CachePerRequest;
use Shamaseen\Repository\Utility\Models\Criteriable;

/**
 * App\Entities\BaseEntity.
 *
 * @property array $searchable
 *
 * @method static Builder whereId($value)
 * @method static Builder newModelQuery()
 * @method static Builder newQuery()
 * @method static Builder query()
 * @mixin Eloquent
 */
class Model extends Eloquent implements CriteriableModelInterface
{
    use Criteriable;
    use CachePerRequest;

    protected $dates = [
        'created_at',
        'updated_at',
    ];
}
