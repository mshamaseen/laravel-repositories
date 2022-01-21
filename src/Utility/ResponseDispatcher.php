<?php

namespace Shamaseen\Repository\Utility;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Shamaseen\Repository\Interfaces\CrudResponse;

class ResponseDispatcher implements CrudResponse
{
    /**
     * @var APIResponses|WebResponses
     */
    private WebResponses|APIResponses $dispatcher;

    public function __construct(Controller $controller)
    {
        if (config('repository.responses') === 'api') {
            $this->dispatcher = (new APIResponses($controller));
        } elseif (config('repository.responses') === 'web') {
            $this->dispatcher = (new WebResponses($controller));
        } elseif ($controller->isAPI) {
            $this->dispatcher = (new APIResponses($controller));
        } else {
            $this->dispatcher = (new WebResponses($controller));
        }
    }

    /**
     * @param Paginator|LengthAwarePaginator $paginate
     * @return View|JsonResponse
     */
    public function index($paginate): View|JsonResponse
    {
        return $this->dispatcher->index($paginate);
    }

    /**
     * @param Model $entity
     *
     * @return View|JsonResponse
     */
    public function show(Model $entity): View|JsonResponse
    {
        return $this->dispatcher->show($entity);
    }

    public function create(): Factory|View|JsonResponse|Application
    {
        return $this->dispatcher->create();
    }

    public function store(Model $entity): JsonResponse|RedirectResponse
    {
        return $this->dispatcher->store($entity);
    }

    public function edit(Model $entity): Factory|View|JsonResponse|Application
    {
        return $this->dispatcher->edit($entity);
    }

    public function update(int $updatedCount): JsonResponse|RedirectResponse
    {
        return $this->dispatcher->update($updatedCount);
    }

    public function destroy(int $destroyedCount): JsonResponse|RedirectResponse
    {
        return $this->dispatcher->destroy($destroyedCount);
    }
}
