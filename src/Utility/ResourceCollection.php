<?php

namespace Shamaseen\Repository\Utility;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Resources\Json\ResourceCollection as LaravelResourceCollection;
use JsonSerializable;

class ResourceCollection extends LaravelResourceCollection
{
    public function toArray($request): array|JsonSerializable|Arrayable
    {
        return $this->collection->map->toCollection($request)->all();
    }
}
