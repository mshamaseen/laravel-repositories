<?php

namespace Shamaseen\Repository\Utility;

use \Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Database\Eloquent\Builder;
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
 * @mixin \Illuminate\Database\Eloquent\Model
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