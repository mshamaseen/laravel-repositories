<?php

namespace Shamaseen\Repository\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use ReflectionClass;
use ReflectionException;
use Shamaseen\Generator\Generator as GeneratorService;
use Shamaseen\Repository\Traits\PathsResolver;

/**
 * Class RepositoryGenerator.
 */
class Generator extends Command
{
    use PathsResolver;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:repository
    {name : Class (singular) for example User}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create repository files';

    /**
     * @var string
     */
    protected string $modelName;

    /**
     * The path to set the files in.
     *
     * @var string
     */
    protected string $path;
    private GeneratorService $generator;

    // Namespaces
    private string $controllersNamespace;
    private string $repositoriesNamespace;
    private string $requestsNamespace;
    private string $modelsNamespace;
    private string $resourcesNamespace;

    /**
     * Create a new command instance.
     */
    public function __construct(GeneratorService $generator)
    {
        parent::__construct();
        $this->generator = $generator;
    }

    /**
     * Execute the console command.
     *
     */
    public function handle()
    {
        $paths = preg_split(' ([/\\\]) ', $this->argument('name'));

        if (!$paths) {
            return 'Name argument is not correct.';
        }

        $this->modelName = $paths[count($paths) - 1];

        unset($paths[count($paths) - 1]);
        $this->path = implode('/', str_replace('\\', '/', $paths));

        // Configure the generator
        config(['generator.base_path' => '']);

        return $this->makeRepositoryPatternFiles();
    }

    public function makeRepositoryPatternFiles(): bool
    {
        // Paths
        $controller = Config::get('repository.controllers_path');
        $model = Config::get('repository.models_path');
        $repository = Config::get('repository.repositories_path');
        $request = Config::get('repository.requests_path');
        $resource = Config::get('repository.json_resources_path');

        // Parent Classes
        $modelParent = Config::get('repository.model_parent');
        $repositoryParent = Config::get('repository.repository_parent');
        $controllerParent = Config::get('repository.controller_parent');
        $requestParent = Config::get('repository.request_parent');
        $resourceParent = Config::get('repository.resource_parent');

        // Namespaces
        $this->controllersNamespace = Config::get('repository.controllers_namespace');
        $this->repositoriesNamespace = Config::get('repository.repositories_namespace');
        $this->requestsNamespace = Config::get('repository.requests_namespace');
        $this->modelsNamespace = Config::get('repository.models_namespace');
        $this->resourcesNamespace = Config::get('repository.resources_namespace');

        $this->generate('Controller', $controller, $controllerParent);
        $this->generate('Model', $model, $modelParent);
        $this->generate('Request', $request, $requestParent);
        $this->generate('Repository', $repository, $repositoryParent);
        $this->generate('Resource', $resource, $resourceParent);

        $this->dumpAutoload();

        return true;
    }

    /**
     * @param string $path
     *
     * @return bool|object
     * @throws ReflectionException
     *
     */
    public function getEntity(string $path)
    {
        $myClass = 'App\Entities\\' . $path . '\\' . $this->modelName;
        if (!class_exists($myClass)) {
            return false;
        }

        $reflect = new ReflectionClass($myClass);

        return $reflect->newInstance();
    }

    /**
     * Get stub path base on type.
     *
     * @param string $type determine which stub should choose to get content
     *
     * @return string
     */
    protected function getStubPath(string $type): string
    {
        return Config::get('repository.stubs_path') . "/$type.stub";
    }

    /**
     * @param string $type define which kind of files should be generated
     * @param string $typePath the path for current type
     * @param string $parentClass
     *
     * @return bool
     */
    protected function generate(string $type, string $typePath, string $parentClass = ''): bool
    {
        $pathNamespace = $this->path ?
            '\\' . $this->forwardSlashesToBackSlashes($this->path)
            : '';

        $outputPath = $type === 'Model'
            ? $typePath . "/" . $this->path . "/" . $this->modelName . ".php"
            : $typePath . "/" . $this->path . "/" . $this->modelName . $type . ".php";

        $typeNamespace = $this->{Str::plural(lcfirst($type)) . 'Namespace'};

        $this->generator->stub($this->getStubPath($type))
            ->replace('{{parentClass}}', $parentClass) // was base
            ->replace('{{modelName}}', $this->modelName)
            ->replace('{{lcModelName}}', Str::lower($this->modelName))
            ->replace('{{lcPluralModelName}}', Str::plural(Str::lower($this->modelName)))
            ->replace('{{typeNamespace}}', $typeNamespace) // was folder
            ->replace('{{pathNamespace}}', $pathNamespace) // was path
            ->replace('{{RequestsNamespace}}', $this->requestsNamespace)
            ->replace('{{RepositoriesNamespace}}', $this->repositoriesNamespace)
            ->replace('{{ResourcesNamespace}}', $this->repositoriesNamespace)
            ->replace('{{ModelNamespace}}', $this->modelsNamespace)
            ->output($outputPath);

        return true;
    }

    /**
     * Check if folder exist.
     *
     * @param string $path class path
     *
     * @return string
     */
    public function getFolderOrCreate(string $path): string
    {
        if (!file_exists($path)) {
            mkdir($path, 0777, true);
        }

        return $path;
    }

    private function dumpAutoload()
    {
        shell_exec('composer dump-autoload');
    }
}
