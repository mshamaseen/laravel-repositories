<?php

namespace Shamaseen\Repository;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;

class PathResolver
{
    protected string $modelName;
    protected string $userPath;
    protected string $basePath;

    public function __construct($modelName, $userPath, $basePath)
    {
        $this->modelName = $modelName;
        $this->userPath = $userPath;
        $this->basePath = $basePath;
    }

    public static array $configTypePathMap = [
        'Controller' => 'controllers_path',
        'Model' => 'models_path',
        'Repository' => 'repositories_path',
        'Request' => 'requests_path',
        'Resource' => 'json_resources_path',
    ];

    /**
     * @param string $type
     * @return string
     */
    public function outputPath(string $type): string
    {
        return $type === 'Model'
            ? $this->directionFromBase($type) . "/" . $this->modelName . ".php"
            : $this->directionFromBase($type) . "/" . $this->modelName . $type . ".php";
    }

    public function directionFromBase($type): string
    {
        return $this->typePath($type) . $this->pathSection($this->userPath);
    }

    public function absolutePath($type): string
    {
        return base_path($this->basePath ."/" . $this->outputPath($type));
    }

    public function pathSection(?string $section): string
    {
        return $section ? "/".$section : '';
    }

    public function typePath(string $type): string
    {
        return Config::get('repository.'.self::$configTypePathMap[$type]);
    }

    public function getPathRelativeToProject(string $path): string
    {
        if (Str::startsWith($path, base_path())) {
            return Str::after($path, base_path());
        }

        return $path;
    }

    /**
     * Resolve each ../
     * @param string $path
     * @return string
     */
    public function resolvePath(string $path): string
    {
        $paths = explode("/", $path);

        for ($i = 1; $i < count($paths); $i++) {
            if ($paths[$i] === "..") {
                unset($paths[$i-1],$paths[$i]);
            }
        }

        return implode("/", $paths);
    }

    public function typeNamespace(string $type): string
    {
        $resolvedPath = $this->resolvePath($this->basePath . "/". $this->typePath($type) . $this->pathSection($this->userPath));

        $upperCaseEachSection = Str::of($resolvedPath)->explode('/')
            ->reduce(fn ($total, $part) => $total."/".ucfirst($part));

        return Str::of($this->getPathRelativeToProject($upperCaseEachSection))->ltrim('/')->replace("/", "\\");
    }

    /**
     * Get stub path base on type.
     *
     * @param string $type determine which stub should choose to get content
     *
     * @return string
     */
    public function getStubPath(string $type): string
    {
        return Config::get('repository.stubs_path') . "/$type.stub";
    }
}
