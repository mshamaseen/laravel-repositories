<?php

namespace Shamaseen\Repository\Interfaces;

use Illuminate\Database\Eloquent\Builder;

/**
 * @method static Builder orderByCriteria()
 * @method static Builder searchByCriteria()
 * @method static Builder filterByCriteria()
 */
interface CriteriableModelInterface
{
    public function scopeFilterByCriteria($query, array $criteria): Builder;
    public function scopeSearchByCriteria($query, array $criteria): Builder;
    public function scopeOrderByCriteria($query, array $criteria): Builder;
}
