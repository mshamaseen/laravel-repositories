<?php /** @noinspection PhpMultipleClassDeclarationsInspection */

namespace Shamaseen\Repository\Utility;

use App;
use Exception;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\Routing\Controller as LaravelController;
use Symfony\Component\HttpFoundation\Response;
use URL;

/**
 * Class BaseController.
 */
class Controller extends LaravelController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public AbstractRepository $repository;
    public Request $request;

    public int $limit = 10;
    public int $maxLimit = 100;

    public string $pageTitle = '';
    public Collection $breadcrumbs;

    /**
     * Can be either a route name or a URL
     *
     * @var string
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
     *
     * @var bool
     */
    public bool $allowTrashRequest = false;

    public array $params = [];

    public ?string $resource;

    public ResponseDispatcher $responseDispatcher;
    private string $requestClass;
    private ?string $resourceClass;

    /**
     * BaseController constructor.
     *
     * @param AbstractRepository $repository
     * @param string $requestClass
     * @param string|null $resource
     */
    public function __construct(AbstractRepository $repository, string $requestClass, string $resource = null)
    {
        $this->repository = $repository;
        $this->breadcrumbs = new Collection();
        $this->resource = $resource;
        $this->paginateType = $this->paginateType ?? config('repository.default_pagination');
        $this->requestClass = $requestClass;
    }

    /**
     * Any data that depend on the request instance should be inside this callback,
     * Request instance should be initialized after other middlewares.
     *
     * @param $method
     * @param $parameters
     * @return Response
     */
    public function callAction($method, $parameters)
    {
        $this->request = App::make($this->requestClass, ['repository' => $this->repository]);

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
     *
     * @return View|JsonResponse
     */
    public function index()
    {
        $this->breadcrumbs->put('index', [
            'link' => $this->resolveRoute($this->routeIndex),
            'text' => $this->pageTitle,
        ]);

        if ($this->paginateType === 'simple') {
            $paginate = $this->repository->simplePaginate($this->limit, $this->request->all());
        } else {
            $paginate = $this->repository->paginate($this->limit, $this->request->all());
        }

        return $this->responseDispatcher->index($paginate);
    }

    /**
     * Display the specified resource.
     *
     * @param int $entityId
     *
     * @return View|JsonResponse
     */
    public function show(int $entityId)
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
     *
     * @return View|JsonResponse
     */
    public function create()
    {
        $this->breadcrumbs->put('create', [
            'link' => $this->resolveRoute($this->createRoute),
            'text' => trans('repository.create'),
        ]);

        return $this->responseDispatcher->create();
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return JsonResponse|RedirectResponse
     */
    public function store()
    {
        $entity = $this->repository->create($this->request->except(['_token', '_method']));

        return $this->responseDispatcher->store($entity);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $entityId
     *
     * @return View|JsonResponse
     */
    public function edit(int $entityId)
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
     *
     * @param int $entityId
     * @return JsonResponse|RedirectResponse
     */
    public function update(int $entityId)
    {
        $entity = $this->repository->update($entityId, $this->request->except(['_token', '_method']));

        return $this->responseDispatcher->update($entity);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $entityId
     *
     * @return JsonResponse|RedirectResponse
     * @throws Exception
     */
    public function destroy(int $entityId)
    {
        $deleted = $this->repository->delete($entityId);

        return $this->responseDispatcher->destroy($deleted);
    }

    /**
     * Return a URI from a route or URL
     *
     * @param string $route
     * @return string
     */
    public function resolveRoute(string $route): string
    {
        return URL::isValidUrl($route) ? $route : route($route);
    }
}
