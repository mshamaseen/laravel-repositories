<?php

/** @noinspection PhpMultipleClassDeclarationsInspection */

namespace Shamaseen\Repository\Utility;

use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Shamaseen\Repository\Interfaces\CrudResponse;
use Symfony\Component\HttpFoundation\Response;

class APIResponses implements CrudResponse
{
    private Controller $controller;

    public function __construct(Controller $controller)
    {
        $this->controller = $controller;
    }

    public function index(Paginator|Collection $paginate): JsonResponse
    {
        $resource = new $this->controller->collectionClass($paginate);

        $resource->additional = $this->controller->params;

        $response = $resource->response();
        $code = Response::HTTP_OK;

        if ($paginate->hasMorePages()) {
            $code = Response::HTTP_PARTIAL_CONTENT;
        }

        return $response->setStatusCode($code);
    }

    public function show(EloquentModel $entity): JsonResponse
    {
        /**
         * @var JsonResource $resource
         */
        $resource = new $this->controller->resourceClass($entity);
        $resource->additional = $this->controller->params;

        return $resource->response()->setStatusCode(Response::HTTP_OK);
    }

    public function create(): JsonResponse
    {
        return response()->json(
            [
                'message' => __('repository.no_content'),
                'data' => $this->controller->params,
            ],
            Response::HTTP_NO_CONTENT
        );
    }

    public function store(EloquentModel $entity): JsonResponse
    {
        /**
         * @var JsonResource $resource
         */
        $resource = new $this->controller->resourceClass($entity);

        return $resource
            ->additional(array_merge([
                'message' => __('repository.created_successfully'),
            ], $this->controller->params))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function edit(EloquentModel $entity): JsonResponse
    {
        return response()->json(
            [
                'message' => __('repository.no_content'),
                'data' => $this->controller->params,
            ],
            Response::HTTP_NO_CONTENT
        );
    }

    public function update(int|bool|EloquentModel $updatedCount): JsonResponse
    {
        return response()->json(
            [
                'message' => __('repository.modified_successfully'),
                'data' => $this->controller->params,
            ],
            Response::HTTP_OK
        );
    }

    public function destroy(int|bool $destroyedCount): JsonResponse
    {
        return response()->json([
            'message' => __('repository.deleted_successfully'),
            'data' => $this->controller->params,
        ], Response::HTTP_OK);
    }
}
