<?php

// we are not using realpath function here because it will not allow the tests to run.

return [
    // global cache disable, if you use this option all the caches will be disabled
    // can't be overriden by the scopes.
    'disable_cache' => false,
    // we need this to publish language files
    'lang_path' => '../resources/lang',

    // base path to all relative paths, absolute paths wil ignore the base file.
    'base_path' => '', // relative to project directory, DON'T USE 1- absolute paths here or 2- laravel path functions.

    // this is an example of absolute path, which means base path will have no effect on it.
    'stubs_path' => __DIR__.'/../resources/stubs',

    // Relative paths - these must be relative to path roots
    'controllers_path' => 'app/Http/Controllers',
    'repositories_path' => 'app/Repositories',
    'models_path' => 'app/Models',
    'requests_path' => 'app/Http/Requests',
    'json_resources_path' => 'app/Http/Resources',
    'policies_path' => 'app/Policies',
    'tests_path' => 'tests/Feature',

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

    /*
     * Frontend Filters key
     * if you want to filter on all URL parameters, leave it null
     * for example, to only get filters from filters param in this Request URL:
     * ?filters[first]=value&filters[second]=value&param=not a filter
     * use filters instead of null here.
     */
    'filter_key' => null,

    /*
     * This needs to be an array, valid options:
     * repository, controller, model, transformer, policy, input, test
     */
    'default_generated_files' => [
        \Shamaseen\Repository\Commands\Generator::REPOSITORY_OPTION,
        \Shamaseen\Repository\Commands\Generator::CONTROLLER_OPTION,
        \Shamaseen\Repository\Commands\Generator::MODEL_OPTION,
        \Shamaseen\Repository\Commands\Generator::RESOURCE_OPTION,
        \Shamaseen\Repository\Commands\Generator::POLICY_OPTION,
        \Shamaseen\Repository\Commands\Generator::REQUEST_OPTION,
        \Shamaseen\Repository\Commands\Generator::COLLECTION_OPTION,
    ],
];
