<?php

/** @noinspection PhpMultipleClassDeclarationsInspection */

namespace Shamaseen\Repository\Utility;

use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as LaravelController;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class BaseController.
 */
class Controller extends LaravelController
{
    use AuthorizesRequests;
    use DispatchesJobs;
    use ValidatesRequests;

    public AbstractRepository $repository;
    public Request $request;

    public int $limit = 10;
    public int $maxLimit = 100;

    public string $pageTitle = '';
    public Collection $breadcrumbs;

    /**
     * Can be either a route name or a URL.
     */
    public string $routeIndex = '';
    public string $createRoute = '';

    public string $viewIndex = '';
    public string $viewCreate = '';
    public string $viewEdit = '';
    public string $viewShow = '';

    public bool $isAPI = false;

    public ?string $paginateType = null;

    /**
     * Allow returning trash if the frontend user request it.
     */
    public bool $allowTrashRequest = false;

    public array $params = [];

    public ResponseDispatcher $responseDispatcher;
    public string $requestClass = Request::class;
    public ?string $policyClass = null;
    public ?string $resourceClass = Resource::class;
    public ?string $collectionClass = ResourceCollection::class;

    /**
     * BaseController constructor.
     */
    public function __construct(AbstractRepository $repository)
    {
        $this->repository = $repository;
        $this->breadcrumbs = new Collection();
        $this->paginateType = $this->paginateType ?? config('repository.default_pagination');
    }

    /**
     * @throws AuthorizationException
     */
    public function authorizeAction(string $action): void
    {
        if ($this->policyClass && method_exists($this->policyClass, $action)) {
            Gate::policy($this->repository->getModelClass(), $this->policyClass)
                ->authorize($action, $this->repository->getModelClass());
        }
    }

    /**
     * Any data that depend on the request instance should be inside this callback,
     * Request instance should be initialized after other middlewares.
     *
     * @return Response
     *
     * @throws AuthorizationException
     */
    public function callAction($method, $parameters)
    {
        $this->request = App::make($this->requestClass, ['repository' => $this->repository]);

        $this->authorizeAction($method);

        $this->isAPI = $this->request->expectsJson();
        $this->limit = min($this->request->get('limit', $this->limit), $this->maxLimit);

        if ($this->allowTrashRequest) {
            if ($this->request->get('with-trash', false)) {
                $this->repository->withTrash();
            }

            if ($this->request->get('only-trash', false)) {
                $this->repository->onlyTrash();
            }
        }

        $this->request->offsetUnset('only-trash');
        $this->request->offsetUnset('with-trash');
        $this->request->offsetUnset('limit');
        $this->responseDispatcher = new ResponseDispatcher($this);

        return parent::callAction($method, $parameters);
    }

    /**
     * Display a listing of the model.
     */
    public function index(): mixed
    {
        $this->breadcrumbs->put('index', [
            'link' => $this->resolveRoute($this->routeIndex),
            'text' => $this->pageTitle,
        ]);

        if ('simple' === $this->paginateType) {
            $paginate = $this->repository->simplePaginate($this->limit, $this->request->all());
        } else {
            $paginate = $this->repository->paginate($this->limit, $this->request->all());
        }

        return $this->responseDispatcher->index($paginate);
    }

    /**
     * Display the specified resource.
     */
    public function show(int|string $entityId): mixed
    {
        $this->breadcrumbs->put('view', [
            'link' => '',
            'text' => __('repository.show'),
        ]);

        $entity = $this->repository->findOrFail($entityId);

        return $this->responseDispatcher->show($entity);
    }

    /**
     * Show the form to create a new resource, only for web responses.
     */
    public function create(): mixed
    {
        $this->breadcrumbs->put('create', [
            'link' => $this->resolveRoute($this->createRoute),
            'text' => trans('repository.create'),
        ]);

        return $this->responseDispatcher->create();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(): mixed
    {
        $entity = $this->repository->create($this->request->except(['_token', '_method']));

        return $this->responseDispatcher->store($entity);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(int|string $entityId): mixed
    {
        $this->breadcrumbs->put('edit', [
            'link' => '',
            'text' => __('repository.edit'),
        ]);

        $entity = $this->repository->findOrFail($entityId);

        return $this->responseDispatcher->edit($entity);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(int|string $entityId): mixed
    {
        $updatedCount = $this->repository->update($entityId, $this->request->except(['_token', '_method']));

        return $this->responseDispatcher->update($updatedCount);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @throws Exception
     */
    public function destroy(int|string $entityId): mixed
    {
        $deletedCount = $this->repository->delete($entityId);

        return $this->responseDispatcher->destroy($deletedCount);
    }

    /**
     * Return a URI from a route or URL.
     */
    public function resolveRoute(string $route): string
    {
        return Route::has($route) ? route($route) : $route;
    }
}
