<?php

namespace Shamaseen\Repository\Utility;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Resources\Json\JsonResource;
use JsonSerializable;
use Shamaseen\Repository\Utility\Resources\AnonymousResourceCollection;

class Resource extends JsonResource
{
    /**
     * The array to check when a collection is called.
     */
    public function toCollection(\Illuminate\Http\Request $request): array|Arrayable|JsonSerializable
    {
        return $this->toArray($request);
    }

    /**
     * Create a new anonymous resource collection.
     *
     * @param mixed $resource
     *
     * @return AnonymousResourceCollection
     */
    public static function collection($resource)
    {
        return tap(new AnonymousResourceCollection($resource, static::class), function ($collection) {
            if (property_exists(static::class, 'preserveKeys')) {
                $collection->preserveKeys = true === (new static([]))->preserveKeys;
            }
        });
    }
}
