<?php

namespace Shamaseen\Repository;

use Illuminate\Support\Facades\Config;

class PathResolver
{
    static array $configTypePathMap = [
        'Controller' => 'controllers_path',
        'Model' => 'models_path',
        'Repository' => 'repositories_path',
        'Request' => 'requests_path',
        'Resource' => 'json_resources_path',
    ];

    static array $configTypeNamespaceMap = [
        'Controller' => 'controllers_namespace',
        'Model' => 'models_namespace',
        'Repository' => 'repositories_namespace',
        'Request' => 'requests_namespace',
        'Resource' => 'resources_namespace',
    ];

    function forwardSlashesToBackSlashes($path)
    {
        return str_replace("/", "\\", $path);
    }

    /**
     * @param string $type
     * @param string $userPath path entered by the user
     * @param string $modelName the model name entered by the user
     * @return string
     */
    function outputPath(string $type, string $userPath, string $modelName): string
    {
        // Paths
        $typePath = $this->typePath($type);

        return $type === 'Model'
            ? $typePath . "/" . $userPath . "/" . $modelName . ".php"
            : $typePath . "/" . $userPath . "/" . $modelName . $type . ".php";
    }

    function typePath(string $type): string
    {
        return Config::get('repository.'.self::$configTypePathMap[$type]);
    }

    function typeNamespace(string $type): string
    {
        return Config::get('repository.'.self::$configTypeNamespaceMap[$type]);
    }

    /**
     * Get stub path base on type.
     *
     * @param string $type determine which stub should choose to get content
     *
     * @return string
     */
    function getStubPath(string $type): string
    {
        return Config::get('repository.stubs_path') . "/$type.stub";
    }
}
