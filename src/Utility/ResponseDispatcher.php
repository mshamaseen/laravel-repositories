<?php

namespace Shamaseen\Repository\Utility;

use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Shamaseen\Repository\Interfaces\CrudResponse;

class ResponseDispatcher implements CrudResponse
{
    /**
     * @var APIResponses|WebResponses
     */
    private $dispatcher;

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
    public function index($paginate)
    {
        return $this->dispatcher->index($paginate);
    }

    /**
     * @param Model $entity
     *
     * @return View|JsonResponse
     */
    public function show(Model $entity)
    {
        return $this->dispatcher->show($entity);
    }

    public function create()
    {
        return $this->dispatcher->create();
    }

    public function store(Model $entity)
    {
        return $this->dispatcher->store($entity);
    }

    public function edit(Model $entity)
    {
        return $this->dispatcher->edit($entity);
    }

    public function update(Model $entity)
    {
        return $this->dispatcher->update($entity);
    }

    public function destroy(bool $isDestroyed)
    {
        return $this->dispatcher->destroy($isDestroyed);
    }
}
