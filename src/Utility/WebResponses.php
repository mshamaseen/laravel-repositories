<?php

namespace Shamaseen\Repository\Utility;

use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\View;
use Shamaseen\Repository\Interfaces\CrudResponse;

class WebResponses implements CrudResponse
{
    private Controller $controller;

    public function __construct(Controller $controller)
    {
        $this->controller = $controller;
        View::share('breadcrumbs', $controller->breadcrumbs);
    }

    public function index(Paginator|Collection $paginate): \Illuminate\Contracts\View\View
    {
        View::share('pageTitle', __('repository.list').' '.$this->controller->pageTitle.' | '.Config::get('app.name'));

        return view($this->controller->viewIndex, $this->controller->params)
            ->with('entities', $paginate)
            ->with('createRoute', $this->controller->createRoute)
            ->with('filters', $this->controller->request->all());
    }

    public function show(Model $entity): \Illuminate\Contracts\View\View
    {
        View::share('pageTitle', 'View '.$this->controller->pageTitle.' | '.Config::get('app.name'));

        return view($this->controller->viewShow, $this->controller->params)
            ->with('entity', $entity);
    }

    public function create(): \Illuminate\Contracts\View\View
    {
        View::share('pageTitle', 'Create '.$this->controller->pageTitle.' | '.Config::get('app.name'));

        return view($this->controller->viewCreate, $this->controller->params);
    }

    public function store(Model $entity): RedirectResponse
    {
        return Redirect::to($this->controller->resolveRoute($this->controller->routeIndex))
            ->with('message', __('repository.created_successfully'));
    }

    public function edit(Model $entity): \Illuminate\Contracts\View\View
    {
        return view($this->controller->viewEdit, $this->controller->params)
            ->with('entity', $entity);
    }

    public function update(int|bool|Model $updatedCount): RedirectResponse
    {
        return Redirect::to($this->controller->resolveRoute($this->controller->routeIndex))
            ->with('message', __('repository.modified_successfully'))
            ->with('updatedCount', $updatedCount);
    }

    public function destroy(int|bool $destroyedCount): RedirectResponse
    {
        return Redirect::to($this->controller->resolveRoute($this->controller->routeIndex))
            ->with('message', __('repository.deleted_successfully'))
            ->with('destroyedCount', $destroyedCount);
    }
}
