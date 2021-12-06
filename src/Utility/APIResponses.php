<?php

namespace Shamaseen\Repository\Utility;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
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
        /** @noinspection PhpUndefinedMethodInspection */
        $resource = $this->controller->resource::collection($paginate);

        $response = $resource->response();
        $code = Response::HTTP_OK;

        if ($paginate->hasMorePages()) {
            $code = Response::HTTP_PARTIAL_CONTENT;
        }

        if ($paginate->isEmpty()) {
            $code = Response::HTTP_REQUESTED_RANGE_NOT_SATISFIABLE;
        }

        return $response->setStatusCode($code);
    }

    /**
     * @param Model $entity
     * @return JsonResponse
     */
    public function show(Model $entity): JsonResponse
    {
        /**
         * @var $resource JsonResource
         */
        $resource = new $this->controller->resource($entity);
        return $resource->response()->setStatusCode(Response::HTTP_OK);
    }

    public function create(): JsonResponse
    {
        return response()->json(
            [
                'message' => __('repository.no_content'),
                'data' => []
            ],
            Response::HTTP_NO_CONTENT
        );
    }

    public function store(Model $entity): JsonResponse
    {
        /**
         * @var $resource JsonResource
         */
        $resource = new $this->controller->resource($entity);
        return $resource
            ->additional([
                'message' => __('repository.created_successfully')
            ])
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function edit(Model $entity): JsonResponse
    {
        return response()->json(
            [
                'message' => __('repository.no_content'),
                'data' => []
            ],
            Response::HTTP_NO_CONTENT
        );
    }

    public function update(Model $entity): JsonResponse
    {
        /**
         * @var $resource JsonResource
         */
        $resource = new $this->controller->resource($entity);
        return $resource
            ->additional([
                'message' => __('repository.modified_successfully')
            ])
            ->response()
            ->setStatusCode(Response::HTTP_OK);
    }

    public function destroy(bool $isDestroyed): JsonResponse
    {
        return response()->json([
            'message' => __('repository.deleted_successfully')
        ], Response::HTTP_OK);
    }
}
