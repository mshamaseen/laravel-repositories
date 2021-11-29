<?php
/**
 * Created by PhpStorm.
 * User: Hamza Alayed
 * Date: 12/29/18
 * Time: 9:53 AM.
 */

namespace Shamaseen\Repository\Utility;

use Exception;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\View;

/**
 * Class BaseController.
 */
class Controller extends \App\Http\Controllers\Controller
{
    protected AbstractRepository $repository;
    protected Request $request;

    protected int $limit = 10;
    protected int $maxLimit = 100;

    protected string $pageTitle = '';
    protected Collection $breadcrumbs;

    /**
     * Can be either a route name or a URL
     *
     * @var string
     */
    protected string $routeIndex = '';
    protected string $createRoute = '';

    protected string $viewIndex = '';
    protected string $viewCreate = '';
    protected string $viewEdit = '';
    protected string $viewShow = '';

    protected bool $isAPI = false;

    /**
     * Allow returning trash if the frontend user request it.
     *
     * @var bool
     */
    protected bool $allowTrashRequest = false;

    protected array $params = [];
    private ?JsonResource $resource;

    /**
     * BaseController constructor.
     *
     * @param AbstractRepository $repository
     * @param Request $request
     * @param JsonResource|null $resource
     */
    public function __construct(AbstractRepository $repository, Request $request, JsonResource $resource)
    {
        $this->repository = $repository;
        $this->request = $request;
        $this->breadcrumbs = new Collection();
        $this->isAPI = $request->expectsJson();
        $this->resource = $resource;

        $limit = $request->get('limit', $this->limit);
        if ($limit <= $this->maxLimit) {
            $this->limit = $limit;
        }

        if ($this->allowTrashRequest) {
            if ($request->get('with-trash', false)) {
                $repository->withTrash();
            }

            if ($request->get('only-trash', false)) {
                $repository->trash();
            }
        }

        $request->offsetUnset('only-trash');
        $request->offsetUnset('with-trash');
        $request->offsetUnset('limit');

        View::share('pageTitle', $this->pageTitle . ' | ' . Config::get('app.name'));
        View::share('breadcrumbs', $this->breadcrumbs);
    }

    /**
     * Display a listing of the model.
     *
     *
     */
    public function index()
    {
        /**
         * Todo: Will this update the view share variable?
         */
        $this->breadcrumbs->put('index', [
            'link' => $this->resolveRoute($this->routeIndex),
            'text' => $this->pageTitle,
        ]);
        View::share('pageTitle', __('repository.list').' ' . $this->pageTitle . ' | ' . Config::get('app.name'));


        if (config('repository.defaultPagination') === 'simple') {
            $data = $this->repository->simplePaginate($this->limit, $this->request->all());
        } else {
            $data = $this->repository->paginate($this->limit, $this->request->all());
        }

        if (!$this->isAPI) {
            return view($this->viewIndex, $this->params)
                ->with('entities', $data)
                ->with('createRoute', $this->createRoute)
                ->with('filters', $this->request->all());
        }

        // Todo I can't understand this
        $resource = $this->resource::collection($data);
        if ($data->hasMorePages()) {
            $custom = collect([
                'code' => JsonResponse::HTTP_PARTIAL_CONTENT,
                'message' => __('repository-generator.partial_content')
            ]);
            $resource = $custom->merge(['data' => $resource]);
            return response()->json($resource, JsonResponse::HTTP_PARTIAL_CONTENT);
        }

        if ($data->isEmpty()) {
            $custom = collect([
                'code' => JsonResponse::HTTP_REQUESTED_RANGE_NOT_SATISFIABLE,
                'message' => __('repository-generator.no_content')
            ]);
            $resource = $custom->merge(['data' => $resource]);
            return response()->json($resource, JsonResponse::HTTP_REQUESTED_RANGE_NOT_SATISFIABLE);
        }

        $custom = collect(['code' => JsonResponse::HTTP_OK, 'message' => __('repository-generator.success')]);
        $resource = $custom->merge(['data' => $resource]);
        return response()->json($resource, JsonResponse::HTTP_OK);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Factory|JsonResponse|\Illuminate\View\View
     */
    public function create()
    {
        if (!$this->isAPI) {
            View::share('pageTitle', 'Create ' . $this->pageTitle . ' | ' . Config::get('app.name'));
            $this->breadcrumbs->put('create', [
                'link' => $this->createRoute,
                'text' => trans('repository-generator.create'),
            ]);

            return view($this->viewCreate, $this->params);
        }

        return response()->json(
            [
                'status' => true,
                'message' => __('repository-generator.no_content'),
                'data' => []
            ],
            JsonResponse::HTTP_NO_CONTENT
        );
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return JsonResponse|RedirectResponse
     */
    public function store()
    {
        $entity = $this->interface->create($this->request->except(['_token', '_method']));

        return $this->makeResponse($entity, true);
    }

    /**
     * Display the specified resource.
     *
     * @param int $entityId
     *
     * @return Factory|JsonResponse|RedirectResponse|\Illuminate\View\View
     */
    public function show(int $entityId)
    {
        $entity = $this->interface->find($entityId);
        if (!$this->isAPI) {
            if (!$entity) {
                return Redirect::to($this->routeIndex)->with('warning', __('repository-generator.not_found'));
            }
            View::share('pageTitle', 'View ' . $this->pageTitle . ' | ' . Config::get('app.name'));
            $this->breadcrumbs->put('view', [
                'link' => '',
                'text' => __('repository-generator.show'),
            ]);

            return view($this->viewShow, $this->params)
                ->with('entity', $entity);
        }

        if (!$entity) {
            return response()->json(
                [
                    'status' => false,
                    'message' => __('repository-generator.not_found'),
                    'data' => []
                ],
                JsonResponse::HTTP_NOT_FOUND
            );
        }
        $resource = new $this->resource($entity);
        return response()->json(
            [
                'status' => true,
                'message' => __('repository-generator.success'),
                'data' => $resource
            ],
            JsonResponse::HTTP_OK
        );
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $entityId
     *
     * @return Factory|JsonResponse|RedirectResponse|\Illuminate\View\View
     */
    public function edit(int $entityId)
    {
        $entity = $this->interface->find($entityId);
        if (!$this->isAPI) {
            if (!$entity) {
                return Redirect::to($this->routeIndex)->with('warning', __('repository-generator.not_found'));
            }
            $this->breadcrumbs->put('edit', [
                'link' => '',
                'text' => __('repository-generator.edit'),
            ]);

            return view($this->viewEdit, $this->params)
                ->with('entity', $entity);
        }

        return response()->json(
            [
                'status' => false,
                'message' => __('repository-generator.not_found'),
                'data' => []
            ],
            JsonResponse::HTTP_NOT_FOUND
        );
    }

    /**
     * Update the specified resource in storage.
     *
     * @param int $entityId
     * @return JsonResponse|RedirectResponse
     */
    public function update(int $entityId)
    {
        $entity = $this->interface->update($entityId, $this->request->except(['_token', '_method']));

        return $this->makeResponse($entity, true);
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
        $deleted = $this->interface->delete($entityId);

        return $this->makeResponse($deleted);
    }

    /**
     * Restore the specified resource from storage.
     *
     * @param int $entityId
     *
     * @return JsonResponse|RedirectResponse
     */
    public function restore(int $entityId)
    {
        $entity = $this->interface->restore($entityId);

        return $this->makeResponse($entity);
    }

    /**
     * Restore the specified resource from storage.
     *
     * @param int $entityId
     *
     * @return RedirectResponse
     */
    public function forceDelete(int $entityId)
    {
        $entity = $this->interface->forceDelete($entityId);

        return $this->makeResponse($entity);
    }

    /**
     * Make response for web or json.
     *
     * @param mixed $entity
     * @param bool $appendEntity
     *
     * @return JsonResponse|RedirectResponse
     */
    public function makeResponse($entity, bool $appendEntity = false)
    {
        if (!$this->isAPI) {
            if ($entity) {
                return Redirect::to($this->routeIndex)->with('message', __('repository-generator.success'));
            }

            if (null === $entity) {
                return Redirect::to($this->routeIndex)->with('warning', __('repository-generator.not_found'));
            }

            return Redirect::to($this->routeIndex)->with('error', __('repository-generator.not_modified'));
        }

        if ($entity) {
            if ($appendEntity) {
                return response()->json(
                    [
                        'status' => true,
                        'message' => __('repository-generator.success'),
                        'data' => new JsonResource($entity)
                    ],
                    JsonResponse::HTTP_OK
                );
            }

            return response()->json(
                [
                    'status' => false,
                    'message' => __('repository-generator.no_content'),
                    'data' => []
                ],
                JsonResponse::HTTP_NO_CONTENT
            );
        }

        if (null === $entity) {
            return response()->json(
                [
                    'status' => false,
                    'message' => __('repository-generator.not_found'),
                    'data' => []
                ],
                JsonResponse::HTTP_NOT_FOUND
            );
        }

        return response()->json(
            [
                'status' => false,
                'message' => __('repository-generator.not_modified'),
                'data' => []
            ],
            JsonResponse::HTTP_NOT_MODIFIED
        );
    }

    /**
     * Return a URI from a route or URL
     *
     * @param string $route
     * @return string
     */
    public function resolveRoute(string $route): string
    {
        return \URL::isValidUrl($route) ? $route : route($route);
    }
}
