<?php

// we are not using realpath function here because it will not allow the tests to run.

return [
    // we need this to publish language files
    'lang_path' => '../resources/lang',

    // base path to all relative paths, absolute paths wil ignore the base file.
    'base_path' => 'app', // relative to project directory, DON'T USE 1- absolute paths here or 2- laravel path functions.

    // this is an example of absolute path, which means base path will have no effect on it.
    'stubs_path' => __DIR__.'/../resources/stubs',

    // Relative paths
    'controllers_path' => 'Http/Controllers',
    'repositories_path' => 'Repositories',
    'models_path' => 'Models',
    'requests_path' => 'Http/Requests',
    'json_resources_path' => 'Http/Resources',
    'policies_path' => 'Policies',
    'tests_path' => 'Tests',

    // Parent classes
    'controller_parent' => 'Shamaseen\Repository\Utility\Controller',
    'model_parent' => 'Shamaseen\Repository\Utility\Model',
    'repository_parent' => 'Shamaseen\Repository\Utility\AbstractRepository',
    'request_parent' => 'Shamaseen\Repository\Utility\Request',
    'resource_parent' => 'Shamaseen\Repository\Utility\Resource',
    'collection_parent' => 'Shamaseen\Repository\Utility\ResourceCollection',

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
    'responses' => 'both',
];
