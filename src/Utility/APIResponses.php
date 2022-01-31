<?php

/** @noinspection PhpMultipleClassDeclarationsInspection */

namespace Shamaseen\Repository\Utility;

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

    public function index($paginate): JsonResponse
    {
        $resource = new $this->controller->collectionClass($paginate);

        $response = $resource->response();
        $code = Response::HTTP_OK;

        if ($paginate->hasMorePages()) {
            $code = Response::HTTP_PARTIAL_CONTENT;
        }

        $resource->additional = $this->controller->params;

        return $response->setStatusCode($code);
    }

    public function show(EloquentModel $entity): JsonResponse
    {
        /**
         * @var $resource JsonResource
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
         * @var $resource JsonResource
         */
        $resource = new $this->controller->resourceClass($entity);

        return $resource
            ->additional([
                'message' => __('repository.created_successfully'),
                ...$this->controller->params,
            ])
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

    public function destroy(int $destroyedCount): JsonResponse
    {
        return response()->json([
            'message' => __('repository.deleted_successfully'),
            'data' => $this->controller->params,
        ], Response::HTTP_OK);
    }
}
