<?php
/**
 * Created by PhpStorm.
 * User: Mohammad Shamaseen
 * Date: 10/01/19
 * Time: 09:58 am.
 */
return [
    // Paths
    'app_path' => realpath(__DIR__ . '/../app/'),
    'route_path' => realpath('routes/'),
    'resources_path' => realpath('resources'),
    'stubs_path' => realpath('resources') . '/stubs/',
    'lang_path' => realpath('resources') . '/lang/',
    'controllers_path' => realpath(__DIR__ . '/../app/').'Http\Controllers',
    'requests_path' => realpath(__DIR__ . '/../app/').'Http\Requests',

    // Folders name relative to app path
    'model' => 'Model',
    'repository' => 'Repository',

    //Parent classes
    'controller_parent' => 'Shamaseen\Repository\Generator\Utility\Controller',
    'resource_parent' => 'Shamaseen\Repository\Generator\Utility\JsonResource',
    'model_parent' => 'Shamaseen\Repository\Generator\Utility\Entity',
    'repository_parent' => 'Shamaseen\Repository\Generator\Utility\AbstractRepository',
    'request_parent' => 'Shamaseen\Repository\Generator\Utility\Request',

    /*
     * Available options:
     * simple {For simplePagination},
     * countable {For paginate with page numbers and total count data}
     */
    'defaultPagination' => 'countable'
];
