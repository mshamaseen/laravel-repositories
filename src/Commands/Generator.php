<?php

namespace Shamaseen\Repository\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use Shamaseen\Generator\Generator as GeneratorService;
use Shamaseen\Repository\Events\RepositoryFilesGenerated;
use Shamaseen\Repository\PathResolver;

/**
 * Class RepositoryGenerator.
 */
class Generator extends Command
{

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
    private string $repositoriesNamespace;
    private string $requestsNamespace;
    private string $modelsNamespace;
    private string $resourcesNamespace;
    private PathResolver $pathResolver;

    /**
     * Create a new command instance.
     */
    public function __construct(GeneratorService $generator, PathResolver $pathResolver)
    {
        parent::__construct();
        $this->generator = $generator;
        $this->pathResolver = $pathResolver;
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
        // Parent Classes
        $modelParent = Config::get('repository.model_parent');
        $repositoryParent = Config::get('repository.repository_parent');
        $controllerParent = Config::get('repository.controller_parent');
        $requestParent = Config::get('repository.request_parent');
        $resourceParent = Config::get('repository.resource_parent');

        // Namespaces
        $this->repositoriesNamespace = Config::get('repository.repositories_namespace');
        $this->requestsNamespace = Config::get('repository.requests_namespace');
        $this->modelsNamespace = Config::get('repository.models_namespace');
        $this->resourcesNamespace = Config::get('repository.resources_namespace');

        $this->generate('Controller', $controllerParent);
        $this->generate('Model', $modelParent);
        $this->generate('Request', $requestParent);
        $this->generate('Repository', $repositoryParent);
        $this->generate('Resource', $resourceParent);

        RepositoryFilesGenerated::dispatch($this->path,$this->modelName);

        $this->dumpAutoload();

        return true;
    }

    /**
     * @param string $type define which kind of files should be generated
     * @param string $parentClass
     *
     * @return bool
     */
    protected function generate(string $type, string $parentClass = ''): bool
    {
        $pathNamespace = $this->path ?
            '\\' . $this->pathResolver->forwardSlashesToBackSlashes($this->path)
            : '';

        $outputPath = $this->pathResolver->outputPath($type, $this->path, $this->modelName);

        $typeNamespace = $this->pathResolver->typeNamespace($type);

        $this->generator->stub($this->pathResolver->getStubPath($type))
            ->replace('{{parentClass}}', $parentClass) // was base
            ->replace('{{modelName}}', $this->modelName)
            ->replace('{{lcModelName}}', Str::lower($this->modelName))
            ->replace('{{lcPluralModelName}}', Str::plural(Str::lower($this->modelName)))
            ->replace('{{typeNamespace}}', $typeNamespace) // was folder
            ->replace('{{pathNamespace}}', $pathNamespace) // was path
            ->replace('{{RequestsNamespace}}', $this->requestsNamespace)
            ->replace('{{RepositoriesNamespace}}', $this->repositoriesNamespace)
            ->replace('{{ResourcesNamespace}}', $this->resourcesNamespace)
            ->replace('{{ModelNamespace}}', $this->modelsNamespace)
            ->output($outputPath);

        return true;
    }

    private function dumpAutoload()
    {
        shell_exec('composer dump-autoload');
    }
}
