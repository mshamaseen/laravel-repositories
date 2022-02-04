<?php

namespace Shamaseen\Repository\Utility;

use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Shamaseen\Repository\Interfaces\CrudResponse;

class ResponseDispatcher implements CrudResponse
{
    private WebResponses|APIResponses $dispatcher;

    public function __construct(Controller $controller)
    {
        if ('api' === config('repository.responses')) {
            $this->dispatcher = (new APIResponses($controller));
        } elseif ('web' === config('repository.responses')) {
            $this->dispatcher = (new WebResponses($controller));
        } elseif ($controller->isAPI) {
            $this->dispatcher = (new APIResponses($controller));
        } else {
            $this->dispatcher = (new WebResponses($controller));
        }
    }

    public function index(Paginator|Collection $paginate): View|JsonResponse
    {
        return $this->dispatcher->index($paginate);
    }

    public function show(Model $entity): View|JsonResponse
    {
        return $this->dispatcher->show($entity);
    }

    public function create(): View|JsonResponse
    {
        return $this->dispatcher->create();
    }

    public function store(Model $entity): JsonResponse|RedirectResponse
    {
        return $this->dispatcher->store($entity);
    }

    public function edit(Model $entity): View|JsonResponse
    {
        return $this->dispatcher->edit($entity);
    }

    public function update(int|bool|Model $updatedCount): JsonResponse|RedirectResponse
    {
        return $this->dispatcher->update($updatedCount);
    }

    public function destroy(int|bool $destroyedCount): JsonResponse|RedirectResponse
    {
        return $this->dispatcher->destroy($destroyedCount);
    }
}
