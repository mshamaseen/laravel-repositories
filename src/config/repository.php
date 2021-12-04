<?php

return [
    // Paths
    // we are not using realpath function here because it will not allow the tests to run.
    'route_path' => __DIR__.'/../routes/',
    'resources_path' => __DIR__.'/../resources',
    'stubs_path' => __DIR__.'/../resources' . '/stubs/',
    'lang_path' => __DIR__.'/../resources/lang/',
    'controllers_path' => __DIR__ . '/../app/Http/Controllers',
    'repositories_path' => __DIR__ . '/../app/Repositories',
    'models_path' => __DIR__ . '/../app/Models',
    'requests_path' => __DIR__ . '/../app/Http/Requests',
    'json_resources_path' => __DIR__ . '/../app/Http/Resources',

    // Parent classes
    'controller_parent' => 'Shamaseen\Repository\Utility\Controller',
    'resource_parent' => 'Shamaseen\Repository\Utility\JsonResource',
    'model_parent' => 'Shamaseen\Repository\Utility\Model',
    'repository_parent' => 'Shamaseen\Repository\Utility\AbstractRepository',
    'request_parent' => 'Shamaseen\Repository\Utility\Request',

    // Namespaces
    'controllers_namespace' => 'App\Http\Controllers',
    'repositories_namespace' => 'App\Repositories',
    'requests_namespace' => 'App\Http\Requests',
    'models_namespace' => 'App\Models',
    'resources_namespace' => 'App\Http\Resources',

    /*
     * Available options:
     * simple - For simplePagination,
     * countable - For paginate with page numbers and total count data
     */
    'default_pagination' => 'countable',

    /*
     * Chose what kind of responses should be returned
     * Available Options:
     * api, web, both
     */
    'responses' => 'both'
];
