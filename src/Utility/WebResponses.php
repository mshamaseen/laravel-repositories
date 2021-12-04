<?php

namespace Shamaseen\Repository\Utility;

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

    public function index($paginate)
    {
        View::share('pageTitle', __('repository.list').' ' . $this->controller->pageTitle . ' | ' . Config::get('app.name'));

        return view($this->controller->viewIndex, $this->controller->params)
            ->with('entities',$paginate)
            ->with('createRoute', $this->controller->createRoute)
            ->with('filters', $this->controller->request->all());
    }

    public function show($entity)
    {
        View::share('pageTitle', 'View ' . $this->controller->pageTitle . ' | ' . Config::get('app.name'));

        return view($this->controller->viewShow, $this->controller->params)
            ->with('entity', $entity);
    }

    public function create()
    {
        View::share('pageTitle', 'Create ' . $this->controller->pageTitle . ' | ' . Config::get('app.name'));

        return view($this->controller->viewCreate, $this->controller->params);
    }

    public function store(Model $entity): RedirectResponse
    {
        return Redirect::to($this->controller->resolveRoute($this->controller->routeIndex))
            ->with('message', __('repository.created_successfully'));
    }

    public function edit(Model $entity)
    {
        return view($this->controller->viewEdit, $this->controller->params)
            ->with('entity', $entity);
    }

    public function update(Model $entity): RedirectResponse
    {
        return Redirect::to($this->controller->resolveRoute($this->controller->routeIndex))
            ->with('message',__('repository.modified_successfully'));
    }

    public function destroy(bool $isDestroyed): RedirectResponse
    {
        return Redirect::to($this->controller->resolveRoute($this->controller->routeIndex))
            ->with('message', __('repository.deleted_successfully'));
    }
}
